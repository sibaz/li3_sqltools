<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_sqltools\tests\cases\data\source\database\adapter;

use lithium\data\Connections;
use lithium\data\Schema;

class MySqlTest extends \lithium\test\Unit {

	protected $_classes	= array(
		'adapter' => 'li3_sqltools\extensions\data\source\database\adapter\MySql',
		'mock' => 'li3_sqltools\tests\mocks\data\source\database\adapter\MockMySql'
	);

	protected $_dbConfig = array();

	public $db = null;

	public $dbmock = null;

	public function skip() {
		$adapter = $this->_classes['adapter'];
		$this->skipIf(!$adapter::enabled(), 'MySQL Extension is not loaded');
		$this->_dbConfig = Connections::get('lithium_mysql_test', array('config' => true));
		$hasDb = (isset($this->_dbConfig['adapter']) && $this->_dbConfig['adapter'] == 'MySql');
		$message = 'Test database is either unavailable, or not using a MySQL adapter';
		$this->skipIf(!$hasDb, $message);

		$adapter = $this->_classes['adapter'];
		$this->db = new $adapter($this->_dbConfig);
		$mock = $this->_classes['mock'];
		$this->dbmock = new $mock($this->_dbConfig);
	}

	public function testTableMeta() {
		$data = array(
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci',
			'engine' => 'InnoDB');
		$result = array();
		foreach ($data as $key => $value){
			$result[] = $this->dbmock->tableMeta($key, $value);
		}
		$expected = array(
			'DEFAULT CHARSET=utf8',
			'COLLATE=utf8_unicode_ci',
			'ENGINE=InnoDB');
		$this->assertEqual($expected, $result);
	}

	public function testBuildColumn() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 32,
			'null' => true,
			'comment' => 'test'
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = '`fieldname` varchar(32) DEFAULT NULL COMMENT \'test\'';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 32,
			'null' => false,
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci'
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = '`fieldname` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'float',
			'length' => 10,
			'precision' => 2
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = "`fieldname` decimal(10,2)";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'text',
			'default' => 'value'
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = "`fieldname` text DEFAULT 'value'";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'text',
			'default' => null
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = "`fieldname` text";
		$this->assertEqual($expected, $result);
	}

	public function testBuildTimeColumn() {
		$data = array(
			'name' => 'created',
			'type' => 'datetime',
			'default' => (object) 'CURRENT_TIMESTAMP',
			'null' => false
 		);

		$result = $this->dbmock->buildColumn($data);
		$expected = '`created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'created',
			'type' => 'datetime',
			'default' => (object) 'CURRENT_TIMESTAMP',
			'null' => true
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = '`created` datetime DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'modified',
			'type' => 'datetime',
			'null' => true
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = '`modified` datetime NULL';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'modified',
			'type' => 'datetime',
			'default' => null,
			'null' => true
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = '`modified` datetime NULL';
		$this->assertEqual($expected, $result);
	}

	public function testBuildColumnCast() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'integer',
			'length' => 11,
			'default' => 1
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = "`fieldname` int(11) DEFAULT 1";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'integer',
			'length' => 11,
			'default' => '1'
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = "`fieldname` int(11) DEFAULT 1";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 64,
			'default' => 1
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = "`fieldname` varchar(64) DEFAULT '1'";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'text',
			'default' => 15
		);
		$result = $this->dbmock->buildColumn($data);
		$expected = "`fieldname` text DEFAULT '15'";
		$this->assertEqual($expected, $result);
	}

	public function testBuildColumnBadType() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'varchar(255)',
			'null' => true
		);
		$this->expectException('Column type `varchar(255)` does not exist.');
		$this->dbmock->buildColumn($data);
	}

	public function testBuildIndex() {
		$data = array(
			'PRIMARY' => array('column' => 'id')
		);
		$result = $this->dbmock->invokeMethod('_buildIndex', array($data, 'tablename'));
		$expected = array('PRIMARY KEY (`id`)');
		$this->assertEqual($expected, $result);

		$data = array(
			'id' => array('column' => 'id', 'unique' => true)
		);
		$result = $this->dbmock->invokeMethod('_buildIndex', array($data, 'tablename'));
		$expected = array('UNIQUE KEY `id` (`id`)');
		$this->assertEqual($expected, $result);

		$data = array(
			'myIndex' => array('column' => array('id', 'name'), 'unique' => true)
		);
		$result = $this->dbmock->invokeMethod('_buildIndex', array($data, 'tablename'));
		$expected = array('UNIQUE KEY `myIndex` (`id`, `name`)');
		$this->assertEqual($expected, $result);
	}

	public function testCreateSchema() {
		$schema = new Schema(array(
			'fields' => array(
				'id' => array('type' => 'integer', 'key' => 'primary'),
				'name' => array(
					'type' => 'string',
					'length' => 255,
					'null' => false,
					'comment' => 'comment'
				),
				'published' => array(
					'type' => 'datetime',
					'null' => false,
					'default' => (object) 'CURRENT_TIMESTAMP'
				),
				'decimal' => array(
					'type' => 'float',
					'length' => 10,
					'precision' => 2
				),
				'integer' => array(
					'type' => 'integer',
					'use' => 'bigint',
					'length' => 10,
					'precision' => 2
				),
				'date' => array(
					'type' => 'date',
					'null' => false,
				),
				'text' => array(
					'type' => 'text',
					'null' => false,
				)
			)
		));

		$result = $this->dbmock->dropSchema('test_table');
		$this->assertTrue($result);

		$expected = "CREATE TABLE `test_table` (\n";
		$expected .= "`id` int(11) NOT NULL AUTO_INCREMENT,\n";
		$expected .= "`name` varchar(255) NOT NULL COMMENT 'comment',\n";
		$expected .= "`published` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
		$expected .= "`decimal` decimal(10,2),\n";
		$expected .= "`integer` bigint(10),\n";
		$expected .= "`date` date NOT NULL,\n";
		$expected .= "`text` text NOT NULL,\n";
		$expected .= "PRIMARY KEY (`id`));";

		$result = $this->dbmock->createSchema('test_table', $schema);
		$this->assertEqual($expected, $result);

		$schema = new Schema(array(
			'fields' => array(
				'id' => array('type' => 'integer', 'key' => 'primary'),
				'stringy' => array(
					'type' => 'string',
					'length' => 128,
					'null' => true,
					'charset' => 'cp1250',
					'collate' => 'cp1250_general_ci',
				),
				'other_col' => array(
					'type' => 'string',
					'null' => false,
					'charset' => 'latin1',
					'comment' => 'Test Comment'
				)
			),
			'meta' => array(
				'indexes' => array('PRIMARY' => array('column' => 'id')),
				'table' => array(
					'charset' => 'utf8',
					'collate' => 'utf8_unicode_ci',
					'engine' => 'InnoDB'
				)
		)));

		$result = $this->dbmock->dropSchema('test_table');
		$this->assertTrue($result);

		$expected = "CREATE TABLE `test_table` (\n";
		$expected .= "`id` int(11) NOT NULL AUTO_INCREMENT,\n";
		$expected .= "`stringy` varchar(128) CHARACTER ";
		$expected .= "SET cp1250 COLLATE cp1250_general_ci DEFAULT NULL,\n";
		$expected .= "`other_col` varchar(255) CHARACTER SET latin1 NOT ";
		$expected .= "NULL COMMENT 'Test Comment',\nPRIMARY KEY (`id`))\n";
		$expected .= "DEFAULT CHARSET=utf8,\nCOLLATE=utf8_unicode_ci,\nENGINE=InnoDB;";

		$result = $this->dbmock->createSchema('test_table', $schema);
		$this->assertEqual($expected, $result);
	}
}
