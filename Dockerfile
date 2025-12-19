FROM node:24-alpine

WORKDIR /usr/src/app

COPY package*.json ./
RUN npm install
COPY . .
RUN chmod +x ./start.sh

CMD ["./start.sh"]