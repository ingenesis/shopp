<?php
/**
* Validate Shopp Schema file
*/

class SchemaTests extends ShoppTestCase {

	function test_shopp_schema () {
		$this->AssertTrue(file_exists(SHOPP_DBSCHEMA));

		// grab the schema
		ob_start();
		include(SHOPP_DBSCHEMA);
		$schema = ob_get_contents();
		ob_end_clean();

		// Strip SQL comments
		$schema = preg_replace('/--\s?(.*?)\n/',"\n",$schema);

		// get a list of tables from the schema
		$this->AssertTrue((bool) preg_match_all("|CREATE TABLE ([^ ]*)|", $schema, $matches));
		$this->AssertTrue(is_array($matches) && isset($matches[1]) && is_array($matches[1]));
		$tables = $matches[1];

		// replace table names with testing table names
		$statements = explode(';', $schema);
		$statements = preg_replace('/('.implode('|', $tables).')[a-z]{0}/', 'schematest_$1', $statements);

		// test run through schema
		$checks = true;
		foreach ( $statements as $i => $statement ) {
			$statement = trim(preg_replace('/\s+/', ' ', $statement));

			if ( $statement ) {
				$checks = $checks && DB::query($statement);
			}
		}

		// cleanup
		foreach ( $tables as $table ) {
			$checks = $checks && DB::query("DROP TABLE schematest_$table");
		}

		$this->AssertTrue($checks);
	}

}