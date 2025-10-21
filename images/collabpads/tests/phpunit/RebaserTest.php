<?php

namespace MediaWiki\Extension\CollabPads\Backend\Tests;

use Exception;
use MediaWiki\Extension\CollabPads\Backend\DAO\MongoDBCollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Model\Author;
use MediaWiki\Extension\CollabPads\Backend\Model\Change;
use MediaWiki\Extension\CollabPads\Backend\Rebaser;
use PHPUnit\Framework\TestCase;

class RebaserTest extends TestCase {

	/**
	 * @param Change $sessionChange
	 * @param Change $change
	 * @param Author $author
	 * @param array $expect
	 * @return void
	 * @throws Exception
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Rebaser::applyChange
	 * @dataProvider provideData
	 */
	public function testRebase( Change $sessionChange, Change $change, Author $author, array $expect ): void {
		$sessionDaoMock = $this->createMock( MongoDBCollabSessionDAO::class );
		$sessionDaoMock->method( 'getChange' )->willReturn( $sessionChange );

		$rebaser = new Rebaser( $sessionDaoMock );
		$result = $rebaser->applyChange( 1, $author, 0, $change );
		$this->assertSame( $expect['start'], $result->getStart() );
		$this->assertSame( [ $expect['tx'] ], $result->serialize( $result->getTransactions() ) );
	}

	/**
	 * @return array[]
	 */
	public function provideData(): array {
		// TODO: Add more
		return [
			'same-start' => [
				new Change( 2, [
					[
						'o' => [
							1, [ '', 'b' ], 5
						],
						'a' => 1
					],
					[
						'o' => [
							3, [ '', 'a' ], 6
						],
						'a' => 1
					]
				], [], [] ),
				new Change( 2, [
					[
						'o' => [
							3, [ '', 'b' ], 6
						],
						'a' => 2
					]
				], [], [] ),
				new Author( 2, 'dummy' ),
				[
					'start' => 4,
					'tx' => [
						'o' => [
							3, [ '', 'b' ], 6
						],
						'a' => 2
					]
				]
			]
		];
	}
}
