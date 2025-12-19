import { WebSocketServer } from 'ws';
import url from 'url';
import crypto from 'crypto';
import http from 'http';
import { createLogger, format, transports } from 'winston';

class WireServer {
	constructor({ port, tokenSalt }) {
		this.logger = this.createLogger();
		this.port = port;
		this.tokenSalt = tokenSalt || '';
		this.server = http.createServer( ( req, res ) => {
			this.handleHttpRequest(req, res);
		} );
		this.wss = new WebSocketServer({ server: this.server, rejectUnauthorized: false } );
		this.server.listen(this.port, () => {
			this.logger.info(`Wire server is listening on port ${this.port}`);
		} );
		this.clients = new Map();
		this.connections = new Map();
		this.setup();
	}

	setup() {
		this.wss.on('connection', async (ws, req) => {
			this.logger.debug( 'Client attempting to connect' );
			const query = url.parse(req.url, true).query;
			const token = query.token;

			const authInfo = await this.verifyToken(token);
			if ( !authInfo ) {
				ws.close(4001, 'Unauthorized');
				return;
			}
			if ( !authInfo.wiki_id || !authInfo.username ) {
				ws.close(4002, 'Invalid auth info');
				return;
			}

			const connId = crypto.randomUUID();
			ws.isAlive = true;
			ws.on('pong', () => {
				ws.isAlive = true;
			} );


			this.connections.set( connId, ws );
			this.clients.set( connId, {
				wikiId: authInfo.wiki_id,
				username: authInfo.username,
				authInfo: authInfo
			} );
			this.logger.debug( 'Client connected', { user: authInfo.username, wikiId: authInfo.wiki_id } );

			ws.on('close', () => this.handleClose(connId));
			ws.on('error', (err) => this.handleError(connId, err));
		});

		this.logger.info('Wire web socket server started');

		// Setup heartbeat
		setInterval( () =>  {
			for ( const [id, ws] of this.connections ) {
				if ( !ws.isAlive ) {
					this.logger.debug( 'Terminating dead connection', { user: this.clients.get(id)?.username } );
					this.handleClose(id);
					return ws.terminate();
				}
				ws.isAlive = false;
				ws.ping();
			}
		}, 30000 ); // 30sec
	}

	async handleHttpRequest(req, res) {
		// Check API key in Bearer
		const authHeader = req.headers.authorization;
		if ( !authHeader || !authHeader.startsWith('Bearer ') || authHeader.split(' ')[1] !== process.env.WIRE_API_KEY ) {
			res.writeHead(401, { 'Content-Type': 'application/json' });
			res.end(JSON.stringify({ error: 'Unauthorized' }));
			this.logger.debug( 'Unauthorized HTTP connection attempted' );
			return;
		}
		// Handle POST request to `/`
		if ( req.method === 'POST' && req.url === '/message' ) {
			let body = '';
			req.on('data', chunk => {
				body += chunk.toString();
			} );
			req.on('end', () => {
				const message = JSON.parse(body);
				// make sure message has `_wiki`, `channel` and `message`
				if ( !message._wiki || !message.channel || !message.payload ) {
					res.writeHead(400, { 'Content-Type': 'application/json' });
					res.end(JSON.stringify({ error: 'Invalid message format' }));
					this.logger.debug( 'Invalid message format received', message );
					return;
				}

				this.logger.debug( 'Received message', message );

				// Broadcast message to all clients for the specified wiki
				const wikiId = message._wiki;
				this.logger.debug( 'Finding clients for wiki', wikiId );
				const clients = {};
				for ( const [connId, info] of this.clients ) {
					if ( info.wikiId === wikiId && this.connections.has(connId) && this.connections.get(connId).isAlive ) {
						clients[connId] = this.connections.get(connId);
					}
				}
				if ( Object.keys(clients).length === 0 ) {
					this.logger.debug( `No active clients for wiki ${wikiId}` );
				}

				for ( const [connId, ws] of Object.entries(clients) ) {
					const clientInfo = this.clients.get(connId);
					this.logger.debug( 'Broadcasting message to client', { user: clientInfo.username, wikiId: clientInfo.wikiId } );
					ws.send(JSON.stringify({
						type: 'message',
						channel: message.channel,
						payload: message.payload
					}));
				}

				res.writeHead(200, { 'Content-Type': 'application/json' });
				res.end(JSON.stringify({ status: 'ok' }));
			} );
		}
	}

	async verifyToken(tokenB64) {
		let data;
		if ( !tokenB64 || typeof tokenB64 !== 'string' ) {
			return false;
		}
		try {
			data = JSON.parse(Buffer.from(tokenB64, 'base64').toString());
		} catch {
			return false;
		}

		let wikiBase = process.env.WIRE_WIKI_BASE_PATH || null;
		if ( !wikiBase ) {
			this.logger.error( 'WIRE_WIKI_BASE_PATH not set in environment' );
			return false;
		}
		const { verifyCallback, token, sig } = data;
		const expectedSig = crypto.createHmac( 'sha256', this.tokenSalt )
			.update( `${verifyCallback}${token}` )
			.digest( 'hex' );
		if ( !crypto.timingSafeEqual(Buffer.from(sig, 'hex'), Buffer.from(expectedSig, 'hex')) ) {
			this.logger.error( 'Invalid token signature', { verifyCallback, token, sig, expectedSig } );
			return false;
		}
		wikiBase = wikiBase.replace( /\/+$/, '' );
		const url = `${wikiBase}${verifyCallback}/mws/v1/user-token/verify/${token}`
		try {
			const response = await fetch(url, { method: 'GET' });
			if ( response.ok ) {
				const authInfo = await response.json();
				if ( !authInfo || !authInfo.username ) {
					console.error('Invalid token response:', authInfo);
					return false;
				}

				return authInfo;
			} else {
				this.logger.error( 'Token verification failed', { status: response.status, statusText: response.statusText } );
				return false;
			}
		} catch (error) {
			this.logger.error( 'Token verification error', { error: error.message } );
			return false;
		}
	}

	handleClose(connId) {
		const client = this.clients.get(connId);
		this.logger.debug( 'Client disconnected', { user: client?.username } );
		this.clients.delete( connId );
		this.connections.delete( connId );
	}

	handleError(connId, err) {
		const client = this.clients.get(connId);
		this.logger.error( 'WebSocket error', { error: err.message, user: client?.username } );
	}

	createLogger() {
		return createLogger({
			level: process.env.WIRE_LOG_LEVEL || 'warning',
			format: format.combine(
				format.colorize(),
				format.timestamp(),
				format.printf(({ level, message, timestamp, ...meta }) => {
					const metaString = meta && Object.keys( meta ).length ? JSON.stringify(meta) : '';
					return `[${timestamp}] ${level}: ${message} ${metaString}`;
				})
			),
			transports: [
				new transports.Console()
			]
		});
	}
}

new WireServer({
	port: process.env.WIRE_PORT || 3333,
	tokenSalt: process.env.WIRE_TOKEN_SALT
});