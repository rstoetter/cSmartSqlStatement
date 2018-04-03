<?php





//
// TODO: cSqlStatement und cSmartSqlStatement in C++ mit antlr ( hat schon eine Grammatik für mysql ) für das CLI
// TODO: programmieren, da db2phpsite viel Zeit mit cSmartSqlStatement verbringt
// TODO: Vorteil: Synatxbaum wird erzeugt für den Treewalker und bei Änderungen am SQL ist kein neuer Scan nötig, da
// TODO: ja direkt am Baum gearbeitet werden kann
//


// cSmartSqlStatement ist eine Erweiterung von cSqlStatement, die ganze SQL-Klauseln einsortieren kann
// UNION wird nicht unterstützt - kann nicht abgebildet weden - kann über mehrere Objekte erzeugt und dann zusammengefügt werden

// Die Debug-Option wurde entfernt, weil die 50 if .. - Abfragen ein Drittel der Zeit konsumierten

// das Umschreiben des Statements wurde aufgegeben, weil dies eine Rekursion erforderte, um das neue "schöne" Statement
// in der Klasse bekannt zu machen und das kostete eine Verdopplung der Abarbeitungszeit


/**
  *
  * The class cSmartSqlStatement helps to manage clauses and parts of SQL statements. The namespace is rstoetter\cSmartSqlStatement.
  *
  * @author Rainer Stötter
  * @copyright 2016-2017 Rainer Stötter
  * @license MIT
  * @version =1.0
  *
  */


  
namespace rstoetter\cSmartSqlStatement;  

define( '_SQL_GENERATOR_QUERY_TYPE_SELECT', 'SELECT' );  

/*

cSmartSqlStatement:: (108 methods):

  ActChar()
  AddGroupByClause()
  AddHavingClause()
  AddOrderByClause()
  AddTable()
  AddWhereClause()
  ArrayClean()
  Dump()
  DumpState()
  DumpStatementAbgearbeitet()
  DumpStatementRest()
  FollowsJoin()
  FollowsOperator()
  FollowsSubquery()
  GetCh()
  GetFields()
  GetFieldsAsString()
  GetFirstTableName()
  GetGroupByClause()
  GetHavingClause()
  GetLimitClause()
  GetLimitsClause()
  GetOrderByClause()
  GetStatement()
  GetStatementHTML()
  GetTableAliases()
  GetTableCount()
  GetTableDeclaration()
  GetTableNameClause()
  GetTableNames()
  GetWhereClause()
  InArray2()
  IsDistinct()
  IsExtraOption()
  IsExtraIdentifier()
  IsExtraStartIdentifier()
  IsFieldAlias()
  IsNumberStart()
  IsSubquerySQL()
  IsUnaryOperator()
  NextCh()
  NextIdentifier()
  NextToken()
  OLDGetHavingClause()
  OLDSetHavingClause()
  OLD_GetGroupByClause()
  OLD_GetOrderByClause()
  OLD_ScanOrderByCondition()
  OLD_SetGroupByClause()
  OLD_SetOrderByClause()
  RemoveStatement()
  RemoveWhereClause()
  Reset()
  RewindTo()
  ScanAlias()
  ScanCaseStatement()
  ScanConditionalExpression()
  ScanEscapedTableReference()
  ScanFieldList()
  ScanFieldList_OLD()
  ScanGroupByCondition()
  ScanHavingCondition()
  ScanIdentifier()
  ScanIndexHint()
  ScanIndexHintList()
  ScanJoinCondition()
  ScanJoinTable()
  ScanLimitCondition()
  ScanNumber()
  ScanOperator()
  ScanOrderByCondition()
  ScanPartitionlist()
  ScanStatement()
  ScanSubQuery()
  ScanTableFactor()
  ScanTableOrFieldName()
  ScanTableReference()
  ScanTableReferences()
  ScanTableSpecification()
  ScanUntilFolgezeichen()
  ScanWhereCondition()
  SetColumn()
  SetDebugLevel()
  SetDistinct()
  SetExtraIdentifier()
  SetExtraStartIdentifier()
  SetFields()
  SetGroupByClause()
  SetHavingClause()
  SetLimitClause()
  SetLimits()
  SetOrderByClause()
  SetTableNames()
  SetWhereClause()
  SkipSpaces()
  StartsBinary()
  StartsHex()
  StringFoundIn()
  UnGetCh()
  _FoundClausePart()
  _RemoveStartingKomma()
  _SeekClausePart()
  _StartingKomma()
  _StartingOperator()
  _construct()
  _destruct()
  is_ctype_number()
  is_ctype_number_start()
  in_array_icase()
  is_ctype_dbfield()
  is_ctype_identifier()
  is_ctype_identifier_start()



  */


// TODO: Aliases können auch OHNE AS geschieben werden : SELECT columna columnb FROM mytable;<- ist gültig!




class cSmartSqlStatement {

	// Deklarationen für ScanStatement( )
	
	/**
	 * the part of the select statement between select and field list
	 *
	 * @var string
	 */

	public $m_extra = '';		// Extra-Anteil zwischen Select und Feldliste	

    /**
      *
      *
      * @var string $m_chr the character, which is actually parsed
      *
      */  	
	
	private $m_chr = '';
	
    /**
      *
      *
      * @var string $m_statement the query after the parsing process
      *
      */  	
	
	
	private $m_statement = '';
	
    /**
      *
      *
      * @var int $m_char_index the actual character position in $m_statement
      *
      */  	
	
	
	private $m_char_index = -1;
	
    /**
      *
      *
      * @var int $m_debug_engine the debug level ( 0 = off, 1 = verbose, 2 = very verbose )
      *
      */  	
	
	
	private $m_debug_engine = 0;		// 0, 1 oder 2
	
    /**
      *
      *
      * @var string $m_id_start_extra additional first characters for the start characters of identifiers
      *
      */  	
	
	
	
	protected $m_id_start_extra = '';	// weitere benutzerdefinierte Zeichen für den Start eines Identifieres
	
    /**
      *
      *
      * @var string $m_id_extra additional characters for the body of identifiers
      *
      */  	
	
	
	protected $m_id_extra = '';		// weitere benutzerdefinierte Zeichen für einen Identifier
	
    /**
      *
      *
      * @var bool $m_in_join true, if we are actually parsing a join 
      *
      */  	
	

	protected $m_in_join = false;		// true, wenn gerade ein Join abgearbeitet wird
	
    /**
      *
      *
      * @var array $m_a_join_tables the tables which are used in the actually parsed join condition as strings with the table names
      *
      */  	
	
	
	protected $m_a_join_tables = array( );	// die im Join verwendeten Tabellen
	
    /**
      *
      *
      * @var array $m_a_joins the tables which are used in the actually parsed join with their joins and subqueries
      *
      */  	
	
	protected $m_a_joins = array( );	// die verschiedenen erkannten Tabellen samt Joins und Subquerys
	
    /**
      *
      *
      * @var bool $m_in_curly_braces true, if actually an ODBC join is parsed
      *
      */  	
	
	
	protected $m_in_curly_braces = false;	// wird gerade ein ODBC-Join abgearbeitet?
	
    /**
      *
      *
      * @var array $m_a_columns the field list before the FROM clause as strings with the column names as strings
      *
      */  	
	
	
	protected $m_a_columns = array( );	// die Spalten vor dem FROM
//	protected $m_a_column_positions = array( );	// Die Startpositionen der Spalten

    /**
      *
      *
      * @var array $m_a_tables the table names with the table names as strings
      *
      */  	


	protected $m_a_tables = array( );		// die Tabellennamen
	
    /**
      *
      *
      * @var array $m_a_field_aliases the field aliasses with the aliases as strings or empty strings
      *
      */  	
	
	
	protected $m_a_field_aliases = array( );	// die Aliases bei den Feldnamen
	
    /**
      *
      *
      * @var array $m_a_group_by the fields which belong to the GROUP BY clause as strings with the column names
      *
      */  	
      
      
	/**
	 * the line counter of the limit clause of the query
	 * @var string 
	 */	
	
	
	public $m_limit_count = '';
	
	/**
	 * the from part of the limit clause of the query
	 * @var string 
	 */	
	
	
	public $m_limit_from = '';      
	
	
	
	protected $m_a_group_by = array( );	// die Felder in der GROUP BY - Klausel
	
	
    /**
      *
      *
      * @var bool $m_in_where true, if actually the WHERE clause is parsed
      *
      */  	
	

	// protected $m_a_where = array( );	// die WHERE-Felder
	protected $m_in_where = false;
	
    /**
      *
      *
      * @var string $m_where_clause the WHERE clause after parsing
      *
      */  	
	
	
	protected $m_where_clause = '';
	
	
    /**
      *
      *
      * @var int $m_after_table_references the position after the table references after the FROM keyword
      *
      */  	
	
	
	protected $m_after_table_references = -1;	// die Position nach den Tabellenreferenzen nach dem FROM

	
    /**
      *
      *
      * @var bool $m_in_group_by true, if actually the GROUP BY clause is parsed
      *
      */  	
	
	
	protected $m_in_group_by = false;
	
    /**
      *
      *
      * @var string $m_where_clause the GROUP BY clause after parsing
      *
      */  	
	
	protected $m_group_by_clause = '';
	
    /**
      *
      *
      * @var int $m_after_where the position after the WHERE condition
      *
      */  	
	
	
	protected $m_after_where = -1;	// die Position nach dem WHERE samt den Bedingungen

    /**
      *
      *
      * @var bool $m_in_having true, if actually the HAVING statement is parsed
      *
      */  	
	
	protected $m_in_having = false;
	
    /**
      *
      *
      * @var string $m_where_clause the HAVING clause after parsing
      *
      */  	
	
	
	protected $m_having_clause = '';
	
    /**
      *
      *
      * @var int $m_after_group_by the position after the GROUP BY condition
      *
      */  	
	
	
	protected $m_after_group_by = -1;	// die Position nach dem GROUP .. BY samt den Bedingungen

    /**
      *
      *
      * @var bool $m_in_order_by true, if actually the ORDER BY statement is parsed
      *
      */  	
	
	
	protected $m_in_order_by = false;
	
    /**
      *
      *
      * @var string $m_where_clause the ORDER BY clause after parsing
      *
      */  	
	
	
	protected $m_order_by_clause = '';
	
    /**
      *
      *
      * @var int $m_after_having the position after the HAVING condition
      *
      */  	
	
	
	protected $m_after_having = -1;	// die Position nach dem GROUP BY .. HAVING samt den Bedingungen

	
    /**
      *
      *
      * @var bool $m_in_fieldlist true, if actually the field list is parsed
      *
      */  	
	
	
	protected $m_in_fieldlist = false;
	
    /**
      *
      *
      * @var bool $m_in_case_statement true, if actually a CASE statement is parsed
      *
      */  	
	
	
	protected $m_in_case_statement = false;


	// the start and end position of the elements of the query in the clean query

	
    /**
      *
      *
      * @var string $m_clean_query the clean query combined after the scan
      *
      */  	
	
	
	public $m_clean_query = '';	// the clean query combined after the scan

	//
	
    /**
      *
      * @var int $m_field_start The start position of the field list or -1
      *
      */ 	

	public $m_field_start = -1;	// Startposition der Feldliste
	
	
    /**
      *
      *
      * @var int $m_field_len The length of the field list or 0
      *
      */  		
	
	public $m_field_len = 0;	// Endposition der Feldliste
	
    /**
      *
      * @var int $m_table_start The start position of the table names after FROM or -1
      *
      */ 	
	
	
	
	public $m_table_start = -1;	// Start der Tabellennamen nach dem FROM
	
    /**
      *
      *
      * @var int $m_table_len The length of the table names after FROM or 0
      *
      */  		
	
	
	public $m_table_len = 0;	// Ende der Tabellennamen nach dem FROM
	
    /**
      *
      * @var int $m_where_start The start position of the WHERE clause or -1
      *
      */ 	
	
	
	
	public $m_where_start = -1;	// Start der WHERE-Bedingung
	
    /**
      *
      * @var int $m_where_len The length of the WHERE clause or 0
      *
      */  		
	
	
	public $m_where_len = 0;	// Ende der WHERE-Bedingung
	
    /**
      *
      * @var int $m_group_by_start The start position of the GROUP BY clause or -1
      *
      */ 	
	
	
	public $m_group_by_start = -1;	// Start der GROUP BY-Bedingung
	
    /**
      *
      * @var int $m_group_by_len The length of the GROUP clause or 0
      *
      */  		
	
	
	public $m_group_by_len = 0;	// Ende der GROUP BY-Bedingung
	
    /**
      *
      * @var int $m_having_start The start position of the HAVING clause or -1
      *
      */ 	
	
	
	public $m_having_start = -1;	// Start der HAVING-Bedingung
	
    /**
      *
      * @var int $m_having_len The length of the HAVING clause or 0
      *
      */  		
	
	
	public $m_having_len = 0;	// Ende der HAVING-Bedingung
	
    /**
      *
      * @var int $m_order_by_start The start position of the ORDER BY clause or -1
      *
      */ 	
	
	
	public $m_order_by_start = -1;	// Start der ORDER BY-Bedingung
	
	
    /**
      *
      * @var int $m_order_by_len The length of the ORDER BY clause or 0
      *
      */  		
	
	
	public $m_order_by_len = 0;	// Ende der ORDER BY-Bedingung
	
    /**
      *
      * @var int $m_limit_start The start position of the LIMIT clause or -1
      *
      */ 	
	
	
	public $m_limit_start = -1;	// Start der LIMIT-Bedingung
	
	
    /**
      *
      * @var int $m_limit_len The length of the LIMIT clause or 0
      *
      */  		
	
	
	public $m_limit_len = 0;	// Ende der LIMIT-Bedingung

	// TODO: token start positions bei AddXXXClause( ) und SetXXXClause() mit einbeziehen
	
	
    /**
      *
      * @var int $m_where_token_start Where the WHERE token starts or -1
      *
      */  		
	

	public $m_where_token_start = -1;	// Startposition des Tokens 'WHERE'
	
    /**
      *
      * @var int $m_group_by_token_start Where the GROUP BY token starts or -1
      *
      */  		
	
	
	public $m_group_by_token_start = -1;	// Startposition des Tokens 'GROUP BY'
	
    /**
      *
      * @var int $m_having_token_start Where the HAVING token starts or -1
      *
      */  		
	
	
	public $m_having_token_start = -1;	// Startposition des Tokens 'HAVING'
	
    /**
      *
      * @var int $m_order_by_token_start Where the ORDER BY token starts or -1
      *
      */  		
	
	
	public $m_order_by_token_start = -1;	// Startposition des Tokens 'ORDER BY'
	
    /**
      *
      * @var int $m_limit_token_start Where the LIMIT token starts or -1
      *
      */  		
	
	
	public $m_limit_token_start = -1;	// Startposition des Tokens 'LIMIT'
	
	
    /**
      *
      * @var array $m_a_fields string array with field names. If there are subqueries then this array might be broken as subqueries include commas, too
      *
      */  		
	

	protected $m_a_fields = array( );	// hier sind die Felder drin. Diese können bei inline sqls sonst
						// zerbrochen sein, weil die inlines ja auch Kommas enthalten können

	// Achtung: Neue Deklarationen im Reset( ) berücksichtigen!

	
	/**
	 * The method GetFieldCount( ) returns the number of managed field names
	 *
	 * Example:
	 *
	 *
	 * @return int the number of managed field names in $m_a_columns
	 *
	 */
	
	
	public function GetFieldCount( ) : int {

	    return count( $this->m_a_columns );

	}	// function GetFieldCount( )	

	
	/**
	 * The method GetField( ) returns the the managed field name with the index $index
	 *
	 * Example:
	 *
	 * @param int the index of the desired field name
	 *
	 * @return string the managed field name with the index $index
	 * 
	 */
	

	public function GetField( int $index ) : string {

	    return $this->m_a_columns[ $index ];

	}	// function GetField( )	
	
	
    /**
      *
      * The method _StartingOperator( ) returns 'AND' or 'OR',  if $str_clause starts with an operator. Else it returns an empty string.
      *
      * Example:
      *
      *
      * @param string $str_clause the string to examine
      * @return string 'AND' or 'OR',  if $str_clause starts with an operator. Else it returns an empty string.
      *
      */    	

    private function _StartingOperator( string $str_clause ) : string {

        //
        // liefert den Operator am Anfang der Zeichenkette oder eine leere Zeichenkette
        //

        $str_clause = strtoupper( trim( $str_clause ) );

        if ( substr( $str_clause, 0, 3 ) == 'AND' ) return 'AND';
        if ( substr( $str_clause, 0, 2 ) == 'OR' ) return 'OR';

        return '';

    }	// function _StartingOperator( )
    
    
    /**
      *
      * The method _StartingKomma( ) returns ',' if $str_clause starts with a comma. Else it returns an empty string.
      *
      * Example:
      *
      *
      * @param string $str_clause the string to examine
      * @return string ','  if $str_clause starts with a comma. Else it returns an empty string.
      *
      */    	    

    private function _StartingKomma( string $str_clause ) : string {

	//
	// liefert das Komma am Anfang der Zeichenkette oder eine leere Zeichenkette
	//

        $str_clause = strtoupper( trim( $str_clause ) );

        if ( substr( $str_clause, 0, 1 ) == ',' ) return ',';

        return '';

    }	// function _StartingKomma( )

    
    /**
      *
      * The method _RemoveStartingKomma( ) returns $str_clause without the starting comma 
      *
      * Example:
      *
      *
      * @param string $str_clause the string to examine
      * @return string $str_clause without the starting comma
      *
      */    	    
    
    
    private function _RemoveStartingKomma( string $str_clause ) : string {

        //
        // liefert das Komma am Anfang der Zeichenkette oder eine leere Zeichenkette
        //

        $str_clause = ( trim( $str_clause ) );

        if ( substr( $str_clause, 0, 1 ) == ',' ) return substr( $str_clause, 1 );

        return $str_clause;

    }	// function _RemoveStartingKomma( )
    
    
    /**
      *
      * The method AddTable( ) adds a table name $table_name to the list of tables after the FROM in the statement
      * the whole statement will be rescanned
      *
      * Example:
      *
      *
      * @param string $table_name the table name to add
      *
      */    	    
    

    public function AddTable( string $table_name ) {

/*
http://dev.mysql.com/doc/refman/5.5/en/identifiers.html

    Identifiers are converted to Unicode internally. They may contain these characters:

        Permitted characters in unquoted identifiers: ASCII: [0-9,a-z,A-Z$_] (basic Latin letters, digits 0-9, dollar,
        underscore) Extended: U+0080 .. U+FFFF

        Permitted characters in quoted identifiers include the full Unicode Basic Multilingual Plane (BMP),
        except U+0000: ASCII: U+0001 .. U+007F Extended: U+0080 .. U+FFFF

TODO: allow dollar sign in table names

*/


        $pos = stripos( $this->m_statement, 'from' );

        if ( $pos !== false ) {

            $pos += strlen( 'from' );

            while ( ctype_space( substr ($this->m_statement, $pos++, 1 ) ) );

            while (
            ( $pos < strlen( $this->m_statement) ) &&
            ( ( ctype_alpha( substr ($this->m_statement, $pos, 1 ) ) ) ||
            ( ctype_alnum( substr ($this->m_statement, $pos, 1 ) ) ) ) ||
            (substr ($this->m_statement, $pos, 1 ) == '_' ) ||
            (substr ($this->m_statement, $pos, 1 ) == '$' )
            ) {

            $pos++;
            }

            $query =
                substr( $this->m_statement, 0, $pos  ) .
                ', ' . $table_name . ' ' .
                substr( $this->m_statement, $pos + 1 )
                ;

            $this->ScanStatement( $query, 'SELECT' );

        }



    }	// function AddTable( )
    
    
    /**
      *
      * The method GetFirstTableName( ) returns the first table name out of the list of table names in the statement
      *
      * Example:
      *
      *
      * @return string the first table name
      *
      */    	    
    

    public function GetFirstTableName( ) : string {

/*
http://dev.mysql.com/doc/refman/5.5/en/identifiers.html

    Identifiers are converted to Unicode internally. They may contain these characters:

        Permitted characters in unquoted identifiers: ASCII: [0-9,a-z,A-Z$_] (basic Latin letters, digits 0-9, dollar,
        underscore) Extended: U+0080 .. U+FFFF

        Permitted characters in quoted identifiers include the full Unicode Basic Multilingual Plane (BMP),
        except U+0000: ASCII: U+0001 .. U+007F Extended: U+0080 .. U+FFFF

TODO: allow dollar sign in table names

*/

	$pos_start = 0;
	$table_name = '';

	$pos = stripos( $this->m_statement, 'from' );

	if ( $pos !== false ) {

	    $pos += strlen( 'from' );

	    while ( ctype_space( substr ($this->m_statement, $pos++, 1 ) ) );

	    $pos_start = $pos - 1;

	    while (
		( $pos < strlen( $this->m_statement) ) &&
		( ( ctype_alpha( substr ($this->m_statement, $pos, 1 ) ) ) ||
		  ( ctype_alnum( substr ($this->m_statement, $pos, 1 ) ) ) ) ||
		  (substr ($this->m_statement, $pos, 1 ) == '_' ) ||
		  (substr ($this->m_statement, $pos, 1 ) == '$' )
		  ) {

		$pos++;
	    }

	    $table_name = substr( $this->m_statement, $pos_start, $pos - $pos_start );

	    // echo "<br>found first table name = $table_name";

	  }

	  return $table_name;



    }	// function GetFirstTableName( )


    /**
      *
      * The method AddGroupByClause( ) adds a new GROUP BY clause to the existing GROUP BY
      * The whole statement will be rescanned
      *
      * Example:
      *
	  * $obj_sql_statement = new cSmartSqlStatement('select');
	  * $query = "select * from tbl group by a, b ";
	  * $obj_sql_statement->ScanStatement( $query );
	  * $obj_sql_statement->AddGroupByClause( '  x, y, z  ' );      
      *
      *
      * @param string $str_clause the GROUP BY elements to add
      *
      */    	    



    public function AddGroupByClause( string $str_clause ) {


        if ( ! strlen( trim( $str_clause ) ) ) return;

        //

        $group_by = $this->GetGroupByClause( );

        $komma = $this->_StartingKomma( $str_clause );
        if ( $komma == ',' ) $komma = ''; else $komma = ',';

        if ( strlen( $group_by ) ) {
            $group_by = $group_by ;
            $group_by .= $komma;
            $group_by .= $str_clause;
        } else {
            $group_by .= $this->_RemoveStartingKomma( $str_clause );
        }

        $this->SetGroupByClause( $group_by );


    }	// function AddGroupByClause( )
    
    
    /**
      *
      * The method AddHavingClause( ) adds a new HAVING clause to the existing HAVING
      * The whole statement will be rescanned
      *
      * Example:
      *  $obj_sql_statement = new cSmartSqlStatement('select');
	  *  $query = "select * from tbl group by a, b having h=5 *  12 / w  limit 12";
	  *  $obj_sql_statement->ScanStatement( $query );
	  *  $obj_sql_statement->AddHavingClause( '( x = sqrt(a) , s = 17 , z=o)' );      
      *
      *
      * @param string $str_having the HAVING elements to add
      *
      */    	    
    

    public function AddHavingClause( string $str_having ) {


        if ( ! strlen( trim( $str_having ) ) ) return;

        //

        $operator = $this->_StartingOperator( $str_having );
        if ( $operator == '' ) $operator = 'AND';

        $str_having = '(' . $str_having . ')';

        if ( ! strlen( trim( $this->m_group_by_clause ) ) ) {
            echo "<br> $this->m_statement";
            $this->DumpState( );
            $this->DumpStatementRest( );
            echo("<br> Warnung: cSmartSqlStatement: having ohne group by in AddHavingClause( ) !");
        }

        $having = $this->GetHavingClause( );

        if ( strlen( trim( $having ) ) ) {
            $having = '(' . $having . ') ';
            $having .= $operator . $str_having;
        } else {
            $having .= $str_having;
        }

        $this->SetHavingClause( $having );


    }	// function AddHavingClause( )
    
    /**
      *
      * The method AddOrderByClause( ) adds a new ORDER BY clause to the existing ORDER BY
      * The whole statement will be rescanned
      *
      * Example:
	  *  $obj_sql_statement = new cSmartSqlStatement('select');
	  *  $query = "select * from tbl order by a, b asc ";
	  *  $obj_sql_statement->ScanStatement( $query );
	  *  $obj_sql_statement->AddOrderByClause( ' h, s asc' );      
      *
      *
      * @param string $str_clause the ORDER BY elements to add
      *
      */    	    
    

    public function AddOrderByClause( string $str_clause ) {
    
        // echo "<br> order by clause = '$str_clause'";

        if ( ! strlen( trim( $str_clause ) ) ) return;

        //

        $order_by = trim( $this->GetOrderByClause( ) );

        if ( strlen( $order_by ) ) $str_clause = ',' . $str_clause;

        $komma = $this->_StartingKomma( $str_clause );
        if ( $komma == ',' ) $komma = ''; else $komma = ',';

        $order = '';

            if ( ( strtoupper( substr( $order_by, strlen( $order_by ) - 3 , 3 ) ) ) == 'ASC' ) {
                $order = ' ' . substr( $order_by, strlen( $order_by ) - 3 , 3 ) . ' ';
                $order_by = substr( $order_by, 0, strlen( $order_by ) - 3 -1 );
                // echo "<br> ASC detected - order = $order order_by = $order_by";
            } elseif ( ( strtoupper( substr( $order_by, strlen( $order_by ) - 4  , 4 ) ) ) == 'DESC' ) {
                $order = ' ' . substr( $order_by, strlen( $order_by ) - 4 , 4 ) . ' ';
                $order_by = substr( $order_by, 0, strlen( $order_by ) - 4 -1);
                // echo "<br> DESC detected";
            } else {
                // echo "<br> weder ASC noch DESC in '$order_by'";
            }


        if ( strlen( trim( $order_by ) ) ) {
     	    // echo "<br>AddOrderByClause: adding comma when adding '{$order}' to '{$order_by}'   ";
     	    // echo "<br> str_clause = $str_clause";
            $order_by = ' ' . $order_by . ' ';
            // $order_by .= $komma . $str_clause . $order;
            $order_by .=  $order . $komma . $str_clause ;
            // echo "<br> order_by = $order_by";
        } else {
            $order_by .= $this->_RemoveStartingKomma( $str_clause ) . $order;
        }
        // echo "<br> set order by clause = '$order_by'";
        $this->SetOrderByClause( $order_by  );

    }	// function AddOrderByClause( )


    /**
      *
      * The method GetGroupByClause( ) returns the GROUP BY clause of the statement
      *
      * Example:
      *
      *
      * @return string the GROUP BY clause or an empty string
      *
      */    	    



    public function GetGroupByClause( ) : string {


        return substr( $this->m_statement, $this->m_group_by_start, $this->m_group_by_len ) ;

	// return $this->m_where_clause;

    }	// function GetGroupByClause( )


    /**
      *
      * The method GetHavingClause( ) returns the HAVING clause of the statement
      *
      * Example:
      *
      *
      * @return string the HAVING clause or an empty string
      *
      */    	    
    

    public function GetHavingClause( ): string {


        return substr( $this->m_statement, $this->m_having_start, $this->m_having_len ) ;

	// return $this->m_where_clause;

    }	// function GetHavingClause( )
    
    
    /**
      *
      * The method GetTableNameClause( ) returns the table names of the statement
      *
      * Example:
      *
      *
      * @return string the table names or an empty string
      *
      */    	    
    

    public function GetTableNameClause( ) : string {


        return substr( $this->m_statement, $this->m_table_start, $this->m_table_len ) ;

	// return $this->m_where_clause;

    }	// function GetTableNameClause( )


    /**
      *
      * The method GetOrderByClause( ) returns the ORDER BY clause of the statement
      *
      * Example:
      *
      *
      * @return string the ORDER BY clause or an empty string
      *
      */    	    


    public function GetOrderByClause( ) : string {


        return substr( $this->m_statement, $this->m_order_by_start, $this->m_order_by_len ) ;

	// return $this->m_where_clause;

    }	// function GetOrderByClause( )

    
    /**
      *
      * The method RemoveStatement( ) returns the query $query without the statement $statement 
      *
      * Example:
      *
      *
      * @param string $query the query
      * @param string $statement the statement to remove
      * @return string the query $query without the statement $statement 
      *
      */    	    
    

    private function RemoveStatement( string $query, string $statement ) : string {

        $query = trim( $query );
        $part = substr( $query, - strlen( $statement ) );
        if ( strtoupper( $statement )  == strtoupper( $part ) ) {
            $query = trim ( substr( $query, 0, strlen( $query ) - strlen( $statement ) ) );
        }

        // echo "\n\n RemoveStatement( ) liefert \n$query";

        return $query;

    }
    
    /**
      *
      * The method GetLimitsClause( ) returns the LIMIT clause of the statement
      *
      * Example:
      *
      *
      * @return string the LIMIT clause of the statement
      *
      */        

    public function GetLimitsClause( ) : string {


        return substr( $this->m_statement, $this->m_limit_start, $this->m_limit_len ) ;

	// return $this->m_where_clause;

    }	// function GetLimitsClause( )
    
    /**
      *
      * The method GetLimits( ) returns the from and count part of the LIMIT clause of the query
      *
      * Example:
      *
      *
      * @param string the returned from part of the query 
      * @param string the returned line count part of the query
      *
      */    	
	

	public function GetLimits( string & $from, string & $count ) {

	    $from = $this->m_limit_from;

	    $count = $this->m_limit_count;

	}	// function GetLimits( )    

    
    /**
      *
      * The method GetLimitClause( ) returns the LIMIT clause of the statement
      *
      * Example:
      *
      *
      * @return string the LIMIT clause of the statement
      *
      */            
    
    public function GetLimitClause( ) : string {


        return substr( $this->m_statement, $this->m_limit_start, $this->m_limit_len ) ;

	// return $this->m_where_clause;

    }	// function GetLimitClause( )
    
    /**
      *
      * The method GetWhereClause( ) returns the WHERE clause of the statement
      *
      * Example:
      *
      *
      * @return string the WHERE clause of the statement
      *
      */            

    public function GetWhereClause( ) : string {


	return substr( $this->m_statement, $this->m_where_start, $this->m_where_len ) ;

	// return $this->m_where_clause;

    }	// function GetWhereClause( )


    /**
      *
      * The method SetLimits( ) sets the LIMIT clause of the statement
      *
      * Example:
      *
      *
      * @param string $from the from part from the LIMIT clause of the statement
      * @param string $count the count part from the LIMIT clause of the statement or an empty string. It defaults to an empty string
      *
      */         

    public function SetLimits( string $from, string $count = '' ) {



        if ( strlen( trim( $from ) ) ) {
            $limits = $from;
            if ( ( ! is_null( $count ) ) && ( strlen( trim( $count ) ) ) ) {
                $limits .= ',' . $count;
            }

            $this->SetLimitClause( $limits );

        }




    }	// function SetLimits( )
    
    /**
      *
      * The method SetLimitClause( ) sets the LIMIT clause of the statement
      * The statement will be rescanned
      *
      * Example:
      *
      *
      * @param mixed $mixed the from part and the count part as an array of strings or as a string seperated by a comma
      *
      */         
    

	public function SetLimitClause( $mixed ) {

	    // set the field array new - string or array is allowed as parameter

	    $mixed = trim( $mixed );

	    if ( ( $this->m_limit_len == 0 ) && ( is_string( $mixed ) ) && ( ! strlen( trim( $mixed ) ) ) ) return;
	    if ( ( $this->m_limit_len == 0 ) && ( is_array( $mixed ) ) && ( ! count( $mixed ) ) ) return;

	    // $this->Dump( );

 	    if ( is_array( $mixed ) ) $mixed = implode( ',', $mixed );

// 	    $this->GetFieldsAsString( $str_org_fields );

	    $query = $this->m_statement;

	    // echo "<br> SetFields() mit ary ="; print_r( $mixed );

	    if ( $this->m_limit_len ) {
            $new_query = substr( $query, 0, $this->m_limit_token_start - ( $this->m_limit_len == 0 ? 0 : 1 ) );
	    } else {
            $new_query = $query;
	    }

	    if ( $this->m_limit_len == 0 ) $new_query .= ' LIMIT ';

	    if ( $mixed == '' || $mixed == ',' ) {
            $new_query = $this->RemoveStatement( $new_query, 'LIMIT' );
	    }

	    $new_query .= ' ' . $mixed . ' ';
	    // danach kommt nichts mehr! $new_query .= ' ' . substr( $query, $this->m_limit_start + $this->m_limit_len );

	    $this->ScanStatement( $new_query, 'SELECT' );

	    // Anstatt von Auruf von ScanStatement( ) einfach die Zähler erhöhen

	}	// function SetLimitClause( )
	
	
    /**
      *
      * The method AddWhereClause( ) adds a new WHERE clause to the existing WHERE with the operator AND
      * The whole statement will be rescanned
      *
      * Example:
	  * $obj_sql_statement = new cSmartSqlStatement('select');
	  * $query = "select * from tbl where x= 5 and z = sqrt( v ) ";
	  * $obj_sql_statement->ScanStatement( $query );
	  * $obj_sql_statement->AddWhereClause( '( c = "123" OR d = 25 OR h = gf) ' );      *
      *
      * @param string $str_clause the WHERE elements to add
      *
      */    	    
	

    public function AddWhereClause( string $str_clause ) {

        $where = $this->GetWhereClause( );

        $operator = $this->_StartingOperator( $str_clause );
        if ( $operator == '' ) $operator = 'AND';

        if ( strlen( $where ) ) $where = '(' . $where . ") $operator ";
        $where .= $str_clause;

        $this->SetWhereClause( $where );

    }	// function AddWhereClause( )

    /**
      *
      * The method SetGroupByClause( ) sets a new GROUP BY clause
      * The whole statement will be rescanned
      *
      * Example:
	  * $obj_sql_statement = new cSmartSqlStatement('select');
	  * $query = "select * from tbl where x= 5 and z = sqrt( v ) ";
	  * $obj_sql_statement->ScanStatement( $query );
	  * $obj_sql_statement->SetGroupByClause( '( c = "123" OR d = 25 OR h = gf) ' );      *
      *
      * @param string $str_group_by the new GROUP BY 
      *
      */    


	public function SetGroupByClause( string $str_group_by ) {

	    // set the existing group_by clause

	    if ( $this->m_group_by_len == 0 && ( ! strlen( trim( $str_group_by ) ) ) ) return;

	    $str_group_by = trim( $str_group_by );

	    $query = $this->m_statement;

	    $breakpoint = 0;		// group_by the query has to be split
	    $tail = '';
	    $clause_type = '';
	    $break_it = false;

		// danach: group by, having, order by - und diese Reihenfolge muss auch eingehalten werden

		if ( $this->m_limit_start && $this->m_limit_end ) {
		    $breakpoint = $this->m_limit_token_start;
		    $clause_type = 'limit';
		    $break_it = true;

		}
		if ( $this->m_order_by_start && $this->m_order_by_len ) {
		    $breakpoint = $this->m_order_by_token_start;
		    $clause_type = 'order by';
		    $break_it = true;

		}
		if ( $this->m_having_start && $this->m_having_len ) {
		    $breakpoint = $this->m_having_token_start;
		    $clause_type = 'having';
		    $break_it = true;
		}
/*
		if ( $this->m_group_by_start && $this->m_group_by_len ) {
		    $breakpoint = $this->m_group_by_start;
		    $clause_type = 'group by';
		    $break_it = true;
		}
*/

	    if ( $this->m_group_by_len ) {
		$left = substr( $query, 0, $this->m_group_by_start - ( $this->m_group_by_len == 0 ? 0 : 1 ) );
	    }

	    if ( ( $this->m_group_by_len ) && ( ! strlen( $str_group_by ) ) ) {
		$left = substr( $query, 0, $this->m_group_by_token_start -1 );
	    }

	    if ( ! $this->m_group_by_len ) {
		$left = substr( $query, 0, $breakpoint -1 );
	    }

		if ( $break_it ) {
// 		    $left = substr( $query, 0, $breakpoint -1 );
		    $tail = substr( $query, $breakpoint );
		} else {
// 		    $left = $query;
		}


// 	    if ( $str_group_by == '' ) {
//
// 		$left = $this->RemoveStatement( $new_query, 'by' );
// 		$left = $this->RemoveStatement( $new_query, 'group' );
//
// 	    }

	    if ( $this->m_group_by_len == 0 ) {
		$left .= ' group by ';
	    }

 	    $right = substr( $query, $this->m_group_by_start , $this->m_group_by_len );

 	    $this->RemoveTrailingSemicolon( $str_group_by );
 	    $this->RemoveTrailingSemicolon( $right );
 	    $this->RemoveTrailingSemicolon( $tail );

	    $new_query = $left . ' ' . $str_group_by . ' ' .   $tail;

	    if ( fale ) {

		echo "\n SetGroupByClause() :";
		echo "\n org statement = " . $this->m_statement;
		echo "\n\n left = {$left}";
		echo "\n neuer group_by-part = {$str_group_by}";
		echo "\n right ( altes group_by ) = {$right}";
		echo "\n tail = {$tail}";
		echo "\n";
		echo "\n new query =\n" . $new_query;
		echo "\n--------------------------";
// 		 die( "\n Abbruch" );

	    }


	    $this->ScanStatement( $new_query, 'SELECT' );

	    // Anstatt von Auruf von ScanStatement( ) einfach die Zähler erhöhen

	}	// function SetGroupByClause( )

	
    /**
      *
      * The method SetOrderByClause( ) sets a new ORDER BY clause
      * The whole statement will be rescanned
      *
      * Example:
	  * $obj_sql_statement = new cSmartSqlStatement('select');
	  * $query = "select * from tbl where x= 5 and z = sqrt( v ) ";
	  * $obj_sql_statement->ScanStatement( $query );
	  * $obj_sql_statement->SetOrderBy( 'c desc,d' );      *
      *
      * @param string $str_order_by the new ORDER BY 
      *
      */    
	

	public function SetOrderByClause( string $str_order_by ) {

	    // set the existing order_by clause

	    if ( $this->m_order_by_len == 0 && ( ! strlen( trim( $str_order_by ) ) ) ) return;

	    $str_order_by = trim( $str_order_by );

	    $query = $this->m_statement;

	    $breakpoint = 0;		// order_by the query has to be split
	    $tail = '';
	    $clause_type = '';
	    $break_it = false;

		// danach: limit - und diese Reihenfolge muss auch eingehalten werden

		if ( $this->m_limit_start && $this->m_limit_len ) {
		    $breakpoint = $this->m_limit_token_start;
		    $clause_type = 'limit';
		    $break_it = true;

		}

	    if ( $this->m_order_by_len ) {
		$left = substr( $query, 0, $this->m_order_by_start - ( $this->m_order_by_len == 0 ? 0 : 1 ) );
	    }

	    if ( ( $this->m_order_by_len ) && ( ! strlen( $str_order_by ) ) ) {
		$left = substr( $query, 0, $this->m_order_by_token_start -1 );
	    }

	    if ( ! $this->m_order_by_len ) {
		// ein leeres ORDER BY, auf das maximal ein LIMIT folgen kann

		if ( $break_it ) {
		    $left = substr( $query, 0, $breakpoint - 1 );
		} else {
		    if ( $this->m_limit_len) {

			$left = substr( $query, 0, $this->m_limit_start - 2 );
			$tail = substr( $query, $this->m_limit_start - 1 );

		    } else {

			$left = $query;
			$tail = '';
		    }
		}
// 		echo "<br>len query = " . strlen( $query ) . ' und breakpoint = ' . $breakpoint ;
// 		echo "<br>no len -> left wird zu <br> '$left'" ;

	    }


		if ( $break_it ) {
// 		    $left = substr( $query, 0, $breakpoint -1 );
		    $tail = substr( $query, $breakpoint );
		} else {
		    // $left = $query;
		}



// 	    if ( $str_order_by == '' ) {
//
// 		$left = $this->RemoveStatement( $query, 'by' );
// 		$left = $this->RemoveStatement( $query, 'order' );
//
// 	    }

	    if ( $this->m_order_by_len == 0 ) {
            $left .= ' order by ';
	    }

 	    $right = substr( $query, $this->m_order_by_start , $this->m_order_by_len );

 	    $this->RemoveTrailingSemicolon( $str_order_by );
 	    $this->RemoveTrailingSemicolon( $right );
 	    $this->RemoveTrailingSemicolon( $tail );

	    $new_query = $left . ' ' . $str_order_by . ' ' .   $tail;

	    if ( false ) {

		echo "\n SetOrderByClause() :";
		echo "\n org statement = " . $this->m_statement;
		echo "\n\n left = {$left}";
		echo "\n neuer order_by-part = {$str_order_by}";
		echo "\n right ( altes order_by ) = {$right}";
		echo "\n tail = {$tail}";
		echo "\n";
		echo "\n new query =\n" . $new_query;
		// die( "\n Abbruch" );

	    }


	    $this->ScanStatement( $new_query, 'SELECT' );

	    // Anstatt von Auruf von ScanStatement( ) einfach die Zähler erhöhen

	}	// function SetOrderByClause( )

    /**
      *
      * The method SetWhereClause( ) sets a new WHERE clause
      * The whole statement will be rescanned
      *
      * Example:
	  * $obj_sql_statement = new cSmartSqlStatement('select');
	  * $query = "select * from tbl where x= 5 and z = sqrt( v ) ";
	  * $obj_sql_statement->ScanStatement( $query );
	  * $obj_sql_statement->SetGroupByClause( '( c = "123" OR d = 25 OR h = gf) ' );      *
      *
      * @param string $str_where the new WHERE
      *
      */    


	public function SetWhereClause( string $str_where ) {

	    // set the existing where clause

	    if ( $this->m_where_len == 0 && ( ! strlen( trim( $str_where ) ) ) ) return;

	    $str_where = trim( $str_where );

	    $query = $this->m_statement;

	    $breakpoint = 0;		// where the query has to be split
	    $tail = '';
	    $clause_type = '';
	    $break_it = false;

	    // danach: group by, having, order by, limit - und diese Reihenfolge muss auch eingehalten werden

		if ( $this->m_limit_start && $this->m_limit_len ) {
		    $breakpoint = $this->m_limit_token_start;
		    $clause_type = 'limit';
		    $break_it = true;

		}
		if ( $this->m_order_by_start && $this->m_order_by_len ) {
		    $breakpoint = $this->m_order_by_token_start;
		    $clause_type = 'order by';
		    $break_it = true;

		}
		if ( $this->m_having_start && $this->m_having_len ) {
		    $breakpoint = $this->m_having_token_start;
		    $clause_type = 'having';
		    $break_it = true;
		}
		if ( $this->m_group_by_start && $this->m_group_by_len ) {
		    $breakpoint = $this->m_group_by_token_start;
		    $clause_type = 'group by';
		    $break_it = true;
		}

	    if ( $this->m_where_len ) {
		$left = substr( $query, 0, $this->m_where_start - ( $this->m_where_len == 0 ? 0 : 1 ) );
	    }

	    if ( ( $this->m_where_len ) && ( ! strlen( $str_where ) ) ) {
		$left = substr( $query, 0, $this->m_where_token_start -1 );
	    }

	    if ( ! $this->m_where_len ) {
		$left = substr( $query, 0, $breakpoint -1 );
	    }

		if ( $break_it ) {
//  		    if ( ! strlen( $str_where ) ) $left = substr( $query, 0, $breakpoint -1 );
		    $tail = substr( $query, $breakpoint );
		} else {
		    // $left = $query;
		}


// 	    if ( $str_where == '' ) {
//
// 		$left = $this->RemoveStatement( $new_query, 'where' );
//
// 	    }

	    if ( $this->m_where_len == 0 ) {
		$left .= ' where ';
	    }

 	    $right = substr( $query, $this->m_where_start , $this->m_where_len );

 	    $this->RemoveTrailingSemicolon( $str_where );
 	    $this->RemoveTrailingSemicolon( $right );
 	    $this->RemoveTrailingSemicolon( $tail );

	    $new_query = $left . ' ' . $str_where . ' ' .   $tail;

	    if ( false ) {

		echo "\n\n SetWhereClause() :";
		echo "\n pos where token = $this->m_where_token_start" ;
		echo "\n pos where = $this->m_where_start" ;
		echo "\n breakpoint where = $breakpoint";
		echo "\n org statement = " . $this->GetStatement( );
		echo "\n\n left = {$left}";
		echo "\n neuer where-part = {$str_where}";
		echo "\n right ( altes where ) = {$right}";
		echo "\n tail = '{$tail}'";
		echo "\n\nnew query = $new_query\n";
		echo "\n--------------------------------";
		// die( "\n Abbruch" );

	    }


	    $this->ScanStatement( $new_query, 'SELECT' );

	    // Anstatt von Auruf von ScanStatement( ) einfach die Zähler erhöhen

	}	// function SetWhereClause( )
	
    /**
      *
      * The method SetHavingClause( ) sets a new HAVING clause
      * The whole statement will be rescanned
      *
      * Example:
	  * $obj_sql_statement = new cSmartSqlStatement('select');
	  * $query = "select * from tbl where x= 5 and z = sqrt( v ) ";
	  * $obj_sql_statement->ScanStatement( $query );
	  * $obj_sql_statement->SetHavingClause( '( c = "123" OR d = 25 OR h = gf) ' );      *
      *
      * @param string $str_having the new HAVING
      *
      */    
	


	public function SetHavingClause( str $str_having ) {

	    // set the existing having clause

	    if ( $this->m_having_len == 0 && ( ! strlen( trim( $str_having ) ) ) ) return;

	    $str_having = trim( $str_having );

	    $query = $this->m_statement;

	    $breakpoint = 0;		// having the query has to be split
	    $tail = '';
	    $clause_type = '';
	    $break_it = false;

		// danach: order by, limit - und diese Reihenfolge muss auch eingehalten werden


		if ( ( $this->m_limit_start > 0 ) && ( $this->m_limit_token_start > 0 ) ) {
		    $breakpoint = $this->m_limit_token_start;
		    $clause_type = 'limit';
		    $break_it = true;
		}
		if ( ( $this->m_order_by_start > 0 ) && ( $this->m_order_by_token_start > 0 ) ) {
		    $breakpoint = $this->m_order_by_token_start;
		    $clause_type = 'order by';
		    $break_it = true;
		}

	    if ( $this->m_having_len ) {
		$left = substr( $query, 0, $this->m_having_start - ( $this->m_having_len == 0 ? 0 : 1 ) );
	    }

	    if ( ( $this->m_having_len ) && ( ! strlen( $str_having ) ) ) {
		$left = substr( $query, 0, $this->m_having_token_start -1 );
	    }

	    if ( ! $this->m_having_len ) {
		$left = substr( $query, 0, $breakpoint -1 );
	    }


		if ( $break_it ) {
// 		    $left = substr( $query, 0, $breakpoint -1 );
		    $tail = substr( $query, $breakpoint );
		} else {
// 		    $left = $query;
		}

// 	    if ( $str_having == '' ) {
//
// 		$left = $this->RemoveStatement( $new_query, 'having' );
//
// 	    }

	    if ( $this->m_having_len == 0 ) {
		$left .= ' having ';
	    }

 	    $right = substr( $query, $this->m_having_start , $this->m_having_len );

 	    $this->RemoveTrailingSemicolon( $str_having );
 	    $this->RemoveTrailingSemicolon( $right );
 	    $this->RemoveTrailingSemicolon( $tail );

	    $new_query = $left . ' ' . $str_having . ' ' .   $tail;

	    if ( false ) {

            echo "\n SetHavingClause() :";
            echo "\n org statement = " . $this->m_statement;
            echo "\n\n left = {$left}";
            echo "\n neuer having-part = {$str_having}";
            echo "\n right ( altes having ) = {$right}";
            echo "\n tail = {$tail}";
            echo "\n";
            // die( "\n Abbruch" );

	    }


	    $this->ScanStatement( $new_query, 'SELECT' );

	    // Anstatt von Auruf von ScanStatement( ) einfach die Zähler erhöhen

	}	// function SetHavingClause( )

    /**
      *
      * The method RemoveTrailingSemicolon( ) removes the trailing semicolon from $str
      *
      * Example:
      *
      * @param string $str the query where the trailing semicolon should be removed
      * @return bool true, if a trailing semicolon was removed
      *
      */    


    private function RemoveTrailingSemicolon( string & $str ) : bool {

        if ( substr( trim( $str ), strlen( trim( $str ) ) - 1 , 1 ) == ';' ) {

            $str = trim( $str );

            $str = trim ( substr( $str, 0, strlen( $str ) - 1 ) );

            return true;

        }

        return false;

    }	// function RemoveTrailingSemicolon( )
    
/*    

    private function _SeekClausePart( $clause, &$start, &$end ) {

        $ret = '';
        $start = -1;
        $end = -1;

        $ret = false;

        // $i = $this->m_after_table_references;
        $i = 0;
        while( ctype_space( substr( $this->m_statement, $i, 1 ) ) ) $i++;

        if ( strlen( trim( substr( $this->m_statement, $i ) ) ) &&  ( trim( substr( $this->m_statement, $i ) ) != ';' ) ) {

            $tst = strtoupper( substr( trim( $this->m_statement ), $i, 5 ) );
            if ( $tst == 'WHERE' ) { $i += 5; }

        }

        if ( ! strlen( trim( $clause ) ) ) {
            echo "<br> Warnung: leerer Eintrag für _SeekClausePart( ) ";
            return;
        }

        for ( $i = $i; $i < strlen( $this->m_statement ); $i++ ) {

        if (  $ret = $this-> _FoundClausePart( $i, $clause, $start, $end ) ) {
            break;
        }

        }

        return $ret;

    }	// function _SeekClausePart( )


    private function _FoundClausePart( $index, $clause, &$start, &$end ) {

        $ret = false;

        $i = 0;		// Position im $clause
        $j = $index;	// Position im $this->m_statement
        $start = $index;

        while ( ctype_space( substr( $clause, $i, 1 ) ) ) $i++;
        while ( ctype_space( substr( $this->m_statement, $j, 1 ) ) ) $j++;


        for ( $i = $i; $i < strlen( $clause ); $i++ ) {

            // echo "<br> statement = '" . substr( $this->m_statement, $j ) . "'  clause = '" . substr( $clause, $i) . "'";

            if  ( ctype_space( substr( $clause, $i, 1 ) ) || ( ctype_space( substr( $this->m_statement, $j, 1 ) )  ) ) {
            while ( ctype_space( substr( $clause, $i, 1 ) ) ) $i++;
            while ( ctype_space( substr( $this->m_statement, $j, 1 ) ) ) $j++;
            // continue;
            }

            if ( $i == strlen( $clause ) - 1 ) {
            //  EOS erreicht
            $end = $i -1;
            $ret = true;
            break;
            }

            if ( strtoupper( substr( $clause, $i, 1 ) ) != strtoupper( substr( $this->m_statement, $j, 1 ) ) ) {

            $ret = ( $i == strlen( $clause ) ) ;
            break;
            }

            // echo " Übereinstimmung in '" . substr( $clause, $i, 1) . "' und '" . substr( $this->m_statement, $j, 1 ) . "'";

            $j++;

            while ( ctype_space( substr( $clause, $i, 1 ) ) ) $i++;
            while ( ctype_space( substr( $this->m_statement, $j, 1 ) ) ) $j++;

        }

        if ( ! $ret) {

            // echo "<br> _FoundClausePart:  nicht gefunden : '$clause' in '$this->m_statement'";

            $start = -1;
            $end = -1;

        } else {

            $end = $j ;

            // echo "<br> _FoundClausePart: gefunden: " . substr( $this->m_statement, $start, $end - $start  );

            // abgeschlossen : von $start bis $end" geht die gefundene Zeichenkette
            // -> substr( $this->m_statement, $start, $end - $start + 1 )
    //

        }

        return $ret;

    }	// function _FoundClausePart( )

*/

    /**
      *
      * The method Reset( ) resets the internal state of the instance and changes the query type
      *
      * Example:
      *
      * @param string $query_type the type of the query ( SELECT, UPDATE, DELETE, ADD )
      *
      */    


    public function Reset( string $query_type ) {

        // m_id_extra und m_id_start_extra bleiben erhalten

        $this->m_where_token_start = -1;	// Startposition des Tokens 'WHERE'
        $this->m_group_by_token_start = -1;	// Startposition des Tokens 'GROUP BY'
        $this->m_having_token_start = -1;	// Startposition des Tokens 'HAVING'
        $this->m_order_by_token_start = -1;	// Startposition des Tokens 'ORDER BY'


        $this->m_extra = '';

        $this->m_chr = '';
        $this->m_statement = '';
        $this->m_char_index = -1;
        $this->m_debug_engine = 0;		// 0, 1 oder 2
    //	$this->m_id_start_extra = '';	// weitere benutzerdefinierte Zeichen für den Start eines Identifieres - bleibt erhalten
    //	$this->m_id_extra = '';		// weitere benutzerdefinierte Zeichen für einen Identifier - bleibt erhalten!!
        $this->m_in_join = false;		// true, wenn gerade ein Join abgearbeitet wird
        $this->m_a_join_tables = array( );	// die im Join verwendeten Tabellen
        $this->m_a_joins = array( );		// die erkannten Tabellen
        $this->m_in_curly_braces = false;	// wird gerade ein ODBC-Join abgearbeitet?
        $this->m_a_columns = array( );

        $this->m_a_group_by = array( );
        $this->m_a_tables = array( );		// die Tabellennamen
        $this->m_a_field_aliases = array( );
        $this->m_in_where = false;
        $this->m_where_part = '';
        $this->m_after_table_references = -1;	// die Position nach den Tabellenreferenzen nach dem FROM

        $this->m_in_group_by = false;
        $this->m_group_by_clause = '';
        $this->m_after_where = -1;	// die Position nach dem WHERE samt den Bedingungen

        $this->m_in_having = false;
        $this->m_having_clause = '';
        $this->m_after_group_by = -1;	// die Position nach dem WHERE samt den Bedingungen

        $this->m_in_order_by = false;
        $this->m_order_by_clause = '';
        $this->m_after_having = -1;	// die Position nach dem WHERE samt den Bedingungen

        $this->m_in_fieldlist = false;
        $this->m_in_case_statement = false;

        //
        $this->m_clean_query = '';	// the clean query combined after the scan

        $this->m_field_start = -1;	// Startposition der Feldliste
        $this->m_field_len = 0;	// Endposition der Feldliste
        $this->m_table_start = -1;	// Start der Tabellennamen nach dem FROM
        $this->m_table_len = 0;	// Ende der Tabellennamen nach dem FROM
        $this->m_where_start = -1;	// Start der WHERE-Bedingung
        $this->m_where_len = 0;	// Ende der WHERE-Bedingung
        $this->m_group_by_start = -1;	// Start der GROUP BY-Bedingung
        $this->m_group_by_len = 0;	// Ende der GROUP BY-Bedingung
        $this->m_having_start = -1;	// Start der HAVING-Bedingung
        $this->m_having_len = 0;	// Ende der HAVING-Bedingung
        $this->m_order_by_start = -1;	// Start der ORDER BY-Bedingung
        $this->m_order_by_len = 0;	// Ende der ORDER BY-Bedingung
        $this->m_limit_start = -1;	// Start der LIMIT-Bedingung
        $this->m_limit_end = 0;	// Ende der LIMIT-Bedingung

        $this->m_a_fields = array( );

//        parent::Reset( $query_type );


    }	// function Reset( )

    /**
      *
      * The method IsFieldAlias( ) returns true, if there is an alias named $identifier in the actually scanned query
      *
      * Example:
      *
      * @param string $identifier the alias to search for
      * @return bool true, if there is an alias named $identifier in the actually scanned query
      *
      */    


    public function IsFieldAlias( string $identifier ) : bool {


//     echo '<br>erkannte Aliases ='; cDebugUtilities::PrintArray( $this->m_a_field_aliases );

        for ( $i = 0; $i < count( $this->m_a_field_aliases ); $i++ ) {
            if ( $this->m_a_field_aliases[ $i ] == $identifier ) return true;
        }


        return false;

    }	// function IsFieldAlias( )
    
    /**
      *
      * The method GetTableCount( ) returns the number of tables the actually scanned query is using
      *
      * Example:
      *
      * @return int the number of tables
      *
      */    
    

    public function GetTableCount( ) : int {

        return count( $this->m_a_tables );

    }	// function GetTableCount( )
    
    /**
      *
      * The method GetTableDeclaration( ) returns the table declaration with the index $index from $m_a_tables
      *
      * Example:
      *
      * @return string the table declaration with the index $index from $m_a_tables
      *
      */     

    public function GetTableDeclaration( int $index ) : string {

        return $this->m_a_tables[ $index ] ;

    }	// function GetTable( )


    /**
      *
      * The method GetTableNames( ) returns the table names as an array of strings
      *
      * Example:
      *
      * @param bool $remove_AS true, if the table names should not have an alias. Defaults to true.
      * @return array the table names as an array of strings
      *
      */   

	public function GetTableNames( bool $remove_AS = true ) : array {	// TODO erweitern

	      // wenn gilt remove_AS = true, dann wird der ALIAS, falls vorhanden, entfernt
	      // es können mehrere Tabellennamen von GetTable( ) geliefert werden samt AS-Ersetzungsnamen

	      $ary_table_names = explode( ',',  $this->GetTableNameClause( ) );

	      for ( $i = 0; $i < count( $ary_table_names ); $i++ ) {

            $ary_table_names[ $i ] = trim( $ary_table_names[ $i ] );

	      }

	      // AS-Ersetzungen entfernen
	      //
	      if ( $remove_AS ) {
            for ( $i = 0; $i < count( $ary_table_names ); $i++ ) {

                $ary_table_names[ $i ] = \rstoetter\cSQL\cSQL::RemoveAlias( $ary_table_names[ $i ] );

            }
	      }

	      return $ary_table_names;

	}	// function GetTableNames( )
	
    /**
      *
      * The method SetTableNames( ) sets the table names of the query
      * The query will be rescanned
      *
      * Example:
      *
      * @param mixed $ary the table names as an array of strings or as a comma-seperated string
      *
      */   
	

	public function SetTableNames( array $ary ) {

	    // set the field array new - string or array is allowed as parameter



	    // $this->Dump( );

	    if ( is_array( $ary ) ) $ary = implode( ',', $ary );

	    // echo "<br>SetFields() setting new field list = "; var_dump( explode( ',', $ary ) );



	    $query = $this->m_statement;

	    // echo "<br> SetFields() mit ary ="; print_r( $ary );

	    $new_query = substr( $query, 0, $this->m_table_start - 1 );

	    $new_query .= ' ' . $ary . ' ';
	    $new_query .= ' ' . substr( $query, $this->m_table_start + $this->m_table_len );

// echo "\n table-part =" . substr( $query, $this->m_table_start, $this->m_table_len );
// echo "\n select nach SetTableNames( ):\n $new_query";

	    $this->ScanStatement( $new_query, 'SELECT' );

	    // Anstatt von Auruf von ScanStatement( ) einfach die Zähler erhöhen


	}	// function SetTableNames( )
	
	
    /**
      *
      * The method GetTableAliases( ) returns the table aliases as an array of strings
      *
      * Example:
      *
      * @return array the table aliases as an array of strings
      *
      */   	

	public function GetTableAliases( ) : array {	// TODO erweitern - und korrekt abarbeiten

	      // es können mehrere Tabellennamen von GetTable( ) geliefert werden samt AS-Ersetzungsnamen

	      $ary_table_names = explode( ',',  $this->GetTableNameClause( ) );

	      for ( $i = 0; $i < count( $ary_table_names ); $i++ ) {
		  $ary_table_names[ $i ] = \rstoetter\cSQL\cSQL::GetAliasFromTablename( $ary_table_names[ $i ] );
	      }


	      return $ary_table_names;

	}	// function GetTableNames( )


    /**
      *
      * The method SetDebugLevel( ) sets the actual debuglevel to $level
      *
      * Example:
      *
      * @param int $level the new debug level (0 no debugging,1 verbose,2 very verbose)
      *
      */   	
	
	

    public function SetDebugLevel( int $level ) {

        $this->m_debug_engine = $level;

    }	// function SetDebugLevel( )
    
    /**
      *
      * The method IsExtraOption( ) returns true, if there is an extra part $extra_option in the extra options before the field list
      * The search is case insensitive.
      *
      * Example:
      *
      * @param string the extra option
      * @return bool true, if there is an extra part $extra_option in the extra options before the field list
      *
      */   	
    

    public function IsExtraOption( string $extra_option ) : bool {

        return ( stripos( $this->m_extra, $extra_option ) !== false );

    }	// function IsExtraOption( )
    
    
    /**
      *
      * The method IsDistinct( ) returns true, if the SELECT statement is a distinct one
      *
      * Example:
      *
      * @param bool true, if the SELECT statement is a distinct one
      *
      */   	    

    public function IsDistinct(  ) : bool {

	// distinct kann auch ein distinctfrom sein!

        return ( $this->IsExtraOption( 'distinct' ) && ( ! $this->IsExtraOption( 'distinctfrom' ) ) )  ;

    }	// function IsExtra( )
    
    /**
      *
      * The method SetDistinct( ) sets the active query to a distinct one
      * The statement will be rescanned
      *
      * Example:
      *
      */   	    
    

    public function SetDistinct(  ) {

        if ( ! $this->IsDistinct( ) ) {

            $this->m_statement = trim( $this->m_statement );

            $this->m_statement = 'SELECT DISTINCT' . substr( $this->m_statement, 7 );

            $this->ScanStatement( $this->m_statement );

        }



	// distinct kann auch ein distinctfrom sein!

    }	// function IsExtra( )


    /**
      *
      * The method SetExtraStartIdentifier( ) sets the active start characters of identifiers
      *
      * Example:
      *
      * @param string $str the string with the extra start for identifiers
      */   	    

      
    public function SetExtraStartIdentifier( string $str ) {

        $this->m_id_start_extra = $str;

    }	// function SetExtraStartIdentifier( )
    
    
    /**
      *
      * The method SetExtraIdentifier( ) sets the active characters of identifiers
      *
      * Example:
      *
      * @param string $str the string with the extra characters for identifiers
      */   	    
    

    public function SetExtraIdentifier( string $str ) {

        $this->m_id_extra = $str;

    }	// function SetExtraIdentifier( )
    
    
    /**
      *
      * The method IsExtraStartIdentifier( ) returns true, if $chr is an extra start identifier
      *
      * Example:
      *
      * @param string $chr the charecter to test
      *
      * @return bool true, if $chr is part of the extra start characters of identifiers
      *
      */   	    


    protected function IsExtraStartIdentifier( string $chr ) : bool {

        for ( $i = 0; $i < strlen( $this->m_id_start_extra ); $i++ ) {

            if ( substr( $this->m_id_start_extra, $i , 1 ) == $chr ) return true;

        }

        return false;

    }	// function IsExtraStartIdentifier( )
    
    
    /**
      *
      * The method IsExtraIdentifier( ) returns true, if $chr is an extra character for identifiers
      *
      * Example:
      *
      * @param string $chr the charecter to test
      *
      * @return bool true, if $chr is part of the extra characters of identifiers
      *
      */   	    
    


    protected function IsExtraIdentifier( string $chr ) : bool {

        for ( $i = 0; $i < strlen( $this->m_id_extra ); $i++ ) {

            if ( substr( $this->m_id_extra, $i , 1 ) == $chr ) return true;

        }

        return false;

    }	// function IsExtraIdentifier( )


    /**
      *
      * The constructor for objects of type cSmartSqlStatement
      *
      * Example:
      *
      * @param string $sql_type the type of sql (select, alter, add, delete). Supported is only 'SELECT'
      *
      */   	    
    
    
    function _construct( string $sql_type ) {

      parent::_construct( $sql_type );

    }	// function _construct( )


    /**
      *
      * The destructor for objects of type cSmartSqlStatement
      *
      * Example:
      *
      */   	    

      
    function _destruct( ) {

        parent::_destruct( );

    }	// function _construct( )

    /**
      *
      * The method Dump( ) dumps the actual statement
      *
      * Example:
      *
      * @param bool $ausgiebig true, if more information should be displayed. Defaults to false.
      *
      */   	    


    public function Dump( bool $ausgiebig = false ) {

        parent::Dump( );

        echo "<br> statement = <br> $this->m_statement";

	    if ( true ) {

            // echo "<br> clean query = $this->m_clean_query ";
            echo "<br> query = $this->m_statement ";
            echo "<br>\t fields   = '" . substr( $this->m_statement, $this->m_field_start, $this->m_field_len ) . "'";
            echo "<br>\t tables   = '" . substr( $this->m_statement, $this->m_table_start, $this->m_table_len ) . "'";
            echo "<br>\t where    = '" . substr( $this->m_statement, $this->m_where_start, $this->m_where_len ) . "'";
            echo "<br>\t group by = '" . substr( $this->m_statement, $this->m_group_by_start, $this->m_group_by_len ) . "'";
            echo "<br>\t having   = '" . substr( $this->m_statement, $this->m_having_start, $this->m_having_len ) . "'";
            echo "<br>\t order by = '" . substr( $this->m_statement, $this->m_order_by_start, $this->m_order_by_len ) . "'";
            echo "<br>\t limit    = '" . substr( $this->m_statement, $this->m_limit_start, $this->m_limit_len ) . "'";


            if ( $ausgiebig ) {
                echo "<br> Reststrings:";

                echo "<br> query = $this->m_statement ";
                echo "<br>\t fields   = '" . substr( $this->m_statement, $this->m_field_start ) . "'";
                echo "<br>\t tables   = '" . substr( $this->m_statement, $this->m_table_start ) . "'";
                echo "<br>\t where    = '" . substr( $this->m_statement, $this->m_where_start ) . "'";
                echo "<br>\t group by = '" . substr( $this->m_statement, $this->m_group_by_start ) . "'";
                echo "<br>\t having   = '" . substr( $this->m_statement, $this->m_having_start ) . "'";
                echo "<br>\t order by = '" . substr( $this->m_statement, $this->m_order_by_start ) . "'";
                echo "<br>\t limit    = '" . substr( $this->m_statement, $this->m_limit_start ) . "'";
            }

	    }



    }	// function Dump( );
    
    
    /**
      *
      * The method GetStatement( ) returns the active statement
      *
      * Example:
      *
      * @return string the active statement
      *
      */   	    
    

    public function GetStatement( ) : string {

        return $this->m_statement;

    }	// function GetStatement( )
    
    
    /**
      *
      * The method GetStatementHTML( ) returns the active statement formatted 
      *
      * Example:
      *
      * @return string the active statement formatted in HTML
      *
      */   	    
    

    public function GetStatementHTML( string $msg = '' ) {

        $sql = '<br>' . $this->m_statement;

        $sql = preg_replace( '/[+[:blank:]][fF][rR][oO][mM][+[:blank:]]/', '<b> from </b>', $sql );
        $sql = preg_replace( '/[+[:blank:]][wW][hH][eE][rR][eE][+[:blank:]]/', '<b> where </b>', $sql );
        $sql = preg_replace( '/[+[:blank:]][gG][rR][oO][uU][pP][+[:blank:]][bB][yY][+[:blank:]\n\r]/', '<b> group by </b>', $sql );
        $sql = preg_replace( '/[+[:blank:]][hH][aA][vV][iI][nN][gG][+[:blank:]]/', '<b> having </b>', $sql );
        $sql = preg_replace( '/[+[:blank:]][lL][iI][mM][iI][tT][+[:blank:]]/', '<b> limit </b>', $sql );
        $sql = preg_replace( '/[sS][eE][lL][eE][cC][tT][+[:blank:]]/', '<b> <span style="color:darkred">select</span> </b>', $sql );
        $sql = preg_replace( '/[+[:blank:]][aA][sS][+[:blank:]]([a-zA-Z_]*[a-zA-Z_0-9])/', ' <span style="color:green">as \1</span> ', $sql );


        return $sql . '<br>';

    }	// function GetStatementHTML( )
    
	/**
	 * The method SetExtra( ) sets the extra part beween SELECT and the field list
	 *
	 * @param string the extra part of the query
	 *
	 */


	public function SetExtra( string $extra ) {
		$this->m_extra = $extra;
	}    
    
    /**
      *
      * The method ScanStatement( ) scans the statement $sql_statement
      *
      * Example:
      *
      * @param string $sql_statement the query which should be scanned
      * @param string|null the query type ( SELECT, UPDATE, DELETE, ADD )
      *
      */   	        
    

    public function ScanStatement( string $sql_statement, string $query_type = '' ) {
    
        $query_type = trim( $query_type );

        if ( ( $query_type != '' ) ) if ( strlen( $query_type) ) $this->Reset( $query_type );

        $this->m_statement = trim( $sql_statement );

        $ary_fields = array( );
        $ary_limit = array( '', '' );
        $start_statement = '';
        $tablereferences = '';
        $where_clause = '';
        $group_clause = '';
        $order_clause = '';
        $having_clause = '';
        $pos_act_token = -1;

        $this->m_columns = array( );
        // echo "<br><h2> Starte ScanStatement()</h2>" . ' (' . debug_backtrace()[2]['function'] . ' /' . debug_backtrace()[1]['line'] . ')' ;
        // echo "<br> statement = " . $this->GetStatementHTML();

        if ( trim ( $this->m_statement ) == '' ) {
            // assert( false == true );		// show trace
            throw new \Exception( '<br> Unrecoverable error: ScanStatement( ) got an empty string - no sql statement ' . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')' );
        }

        if ( strtoupper( substr( $this->m_statement, 0, 6 ) ) == 'SELECT' ) {

            //  echo "SELECT-statement detected";

            $this->m_char_index = 5 ;
            $this->GetCh( );
            $this->SkipSpaces( );

            //  echo "<br> betrachte $this->m_chr";

            // start statements zusammensuchen

            $start_statement = '';
            // echo '<br><h3>Scanne Special Start Items</h3>';
            while ( \rstoetter\libsqlphp\cMySql57_Utils::IsStartStatement( $id = $this->ScanIdentifier( ) ) ) {

        // 		  $this->ScanIdentifier( );

                $start_statement .= ' ' . $id;
                if ( $id == 'MAX_STATEMENT_TIME' ) {

                    $this->SkipSpaces( );
                    assert( $this->m_chr == '=' );
                    $this->GetCh( );
                    $this->SkipSpaces( );
                    $value = $this->ScanNumber( );
                    $start_statement .= ' = ' . $value ;

                }
            $this->SkipSpaces( );
            };

            for ( $i = 0; $i < strlen( $id ); $i++ ) { $this->UnGetCh( ); };

            $start_statement .= ' ';

            //  echo "<br> start_statement = $start_statement";

    // 		echo '<br><h3>Scanne Feldliste </h3>';
            // Feldliste zusammensuchen
            $ary_fields = array( );

            $this->m_field_start = $this->m_char_index;

            $this->m_in_fieldlist = true;
            $fieldlist = $this->ScanFieldList( $ary_fields );
            $this->m_in_fieldlist = false;
            $this->m_field_len = $this->m_char_index - $this->m_field_start;

            // echo "<br><b>oben ermittelte Felder</b> =", substr( $this->m_statement, $this->m_field_start, $this->m_field_len ) ;

            // echo "<br>ScanStatement: ary_fields=<br>"; var_dump( $ary_fields );

            $this->m_a_fields = array( );
            for ( $j = 0; $j < count( $ary_fields ); $j++ ) {

            if ( strlen( trim( $ary_fields[ $j ] ) ) ) {

                $this->m_a_fields[] = trim( $ary_fields[ $j ] );

            }
            }

            if ( false ) {
                var_dump( $this->m_a_fields );
                $this->DumpState();
                echo "\n $this->m_statement";
                die("\n Abbruch");
            }

            // FROM-Statement zusammentragen

            $this->SkipSpaces( );
            $identifier = $this->ScanIdentifier( );

            assert( strtoupper( trim( $identifier ) ) == 'FROM' );
            if ( strtoupper( trim( $identifier ) ) != 'FROM' ) {
                echo "<br> cSmartSqlStatement: From erwartet aber nicht gefunden <br> query = <br>" . $this->m_statement;;
                echo "<br> identifier ist " . $identifier;
                echo "<br> m_chr = '" . $this->m_chr . "'";
                echo "<br> bin gerade bei "; $this->DumpStatementRest();
                die( "<br>Abbruch" );
            }

            $this->SkipSpaces( );

    // 		echo "<br> erkannte Spalten=";cDebugUtilities::PrintArray( $this->m_a_columns );

    // 		echo '<br><h3>Scanne Tabellennamen </h3>';
            $this->m_table_start = $this->m_char_index ;	// nach 'FROM'

    ### alles durcheinander - hier und bei SetFields()!

            $tablereferences = $this->ScanTableReferences( );
            $this->m_table_len = $this->m_char_index - $this->m_table_start;
    // echo "<br> nach ScanTableReferences( ) ( $this->m_table_start, $this->m_table_end ) : '" . substr( $this->m_statement, $this->m_table_start, $this->m_table_end - $this->m_table_start ) . "'";
            //  echo "<br> table references =>" . $tablereferences;

            $this->m_after_table_references = $this->m_char_index;

            $chk = trim( substr( $this->m_statement, $this->m_after_table_references ) );
            if ( substr( $chk, 0, 1 ) == ';' ) {
                $this->m_after_table_references = -1;
                $this->m_statement = substr( $this->m_statement, 0, $this->m_char_index  );
                // echo "<br> shortening statement = '$this->m_statement'";
                $this->m_where_part = '';
            }

    // 		echo "<br> erkannte Tabellen=";cDebugUtilities::PrintArray( $this->m_a_tables );


    // 		echo '<br><h3>Scanne restliche Statements </h3>';
            while ( $this->m_chr != '' ) {

            $this->SkipSpaces( );
            $pos_act_token = $this->m_char_index;
            $identifier = $this->ScanTableOrFieldName( );
            $identifier = strtoupper( $identifier );
            assert( ( $this->m_chr == '' ) || ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( $identifier ) ) ) || ( $this->m_chr == ';' ) );

            if ( ! ( ( $this->m_chr == '' ) || ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( $identifier ) ) ) || ( $this->m_chr == ';' ) ) ) {

                echo "<br> Ende oder ClauseStart:m_chr = $this->m_chr";
                $this->DumpState( );
                $this->DumpStatementAbgearbeitet( );
                $this->DumpStatementRest( );

                die( "\n Abbruch in cSmartSqlStatement" );

            }

            //  echo "<br> ScanStatement identifier ==> $identifier";
            $this->SkipSpaces( );

            if ( $this->m_chr == ';' ) {

                $this->GetCh( );

            } elseif ( $identifier == 'WHERE' ) {

                $this->m_where_token_start = $pos_act_token;

                $this->m_where_start = $this->m_char_index;
                $where_clause = $this->ScanWhereCondition( );
                $this->m_where_clause = $where_clause;
                $this->m_where_len = $this->m_char_index - $this->m_where_start;



            } elseif ( $identifier == 'GROUP' ) {

                $this->m_group_by_token_start = $pos_act_token;

                //$identifier = $this->ScanIdentifier( true );

                $identifier = $this->ScanTableOrFieldName( );
                $identifier = strtoupper( $identifier );
                if( strtoupper( $identifier != 'BY' ) ) {
                die("<br> error parsing group by");
                }

                $this->SkipSpaces( );

                if ( $this->m_after_table_references == -1 ) $this->m_after_table_references = $this->m_char_index;
                $this->m_after_where = $this->m_char_index;

                $this->m_group_by_start = $this->m_char_index;
                $group_clause = $this->ScanGroupByCondition( $ary );
                $this->m_a_group_by = $ary;
                $this->m_group_by_len = $this->m_char_index - $this->m_group_by_start;
                $this->m_group_by_clause = $group_clause;

            } elseif ( $identifier == 'HAVING' ) {

                $this->m_having_token_start = $pos_act_token;

                //  echo "<br> working on HAVING";

                $this->SkipSpaces( );

                if ( $this->m_after_table_references == -1 ) $this->m_after_table_references = $this->m_char_index;
                if ( $this->m_after_where == -1 ) die( "<br> having without group by in query <br> $this->m_statement" );

                $this->m_after_having = $this->m_char_index;

                $this->m_having_start = $this->m_char_index;
                $having_clause = $this->ScanHavingCondition( );
                $this->m_having_len = $this->m_char_index - $this->m_having_start;
                //  echo "<br> having clause = $having_clause";
                $this->m_having_clause = $having_clause;

                if ( false ) {
                echo "\n"; var_dump( $this->m_having_clause );
                $this->DumpState();
                echo "\n $this->m_statement";
                die("\n Abbruch nach HAVING");
                }

            } elseif ( $identifier == 'ORDER' ) {

                $this->m_order_by_token_start = $pos_act_token;

                // echo "<br> working on ORDER";

                $identifier = $this->ScanIdentifier( true );
                if( $identifier != 'BY' ) {
                die("<br> error parsing order by in query = '$this->m_statement'");
                }

                $this->SkipSpaces( );

                if ( $this->m_after_table_references == -1 ) $this->m_after_table_references = $this->m_char_index;
                if ( $this->m_after_where == -1 ) $this->m_after_where = $this->m_char_index;
                if ( $this->m_after_having == -1 ) $this->m_after_having = $this->m_char_index;

                $this->m_after_having = $this->m_char_index;

                $this->m_order_by_start = $this->m_char_index;
                
                $ary = array( );
                $order_clause = $this->ScanOrderByCondition( $ary );
                $this->m_order_by_len = $this->m_char_index - $this->m_order_by_start;

                $this->m_order_by_clause = $order_clause ;

                // echo "<br> order by erhält '$order_clause'";


            } elseif ( $identifier == 'LIMIT' ) {

                $this->m_limit_token_start = $pos_act_token;

    // 			$identifier = $this->ScanIdentifier( true );
    /*			if( $identifier != 'BY' ) {
                $this->DumpStatementRest( );
                die("<br> error parsing limit by");

                }
    */

                $this->SkipSpaces( );


                $this->m_limit_start = $this->m_char_index;
                // echo "<br> scanning LIMIT";
                $limit = $this->ScanLimitCondition( $ary_limit );
                $this->m_limit_len = $this->m_char_index - $this->m_limit_start;

                $this->m_limit_from = $ary_limit[0];
                $this->m_limit_count = $ary_limit[1];

            } elseif ( $this->m_chr != '' ) {
                echo "<br>  ScanStatement: m_statement = <br> $this->m_statement";
                $this->DumpStatementRest( );
                echo "<br>m_chr = '" . $this->m_chr . "'";
                echo("<br>unrecoverable error: unknown condition in sql statement : '$identifier'" . __LINE__  .
                ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')' );
                $this->DumpState( );
                $this->DumpStatementRest( );
                die("<br>Abbruch");

            }

            //  echo "<br> Das Rest-Statement ->" . substr( $this->m_statement, $this->m_char_index ) ;

            }	// while

            // now transfer the found data to the base class

            $this->SetExtra( $start_statement );

            // echo "<br> ary_fields in ScanStatement: " ; print_r( $ary_fields );

// ???            $this->ResetColumns( );

            for ( $i = 0; $i < count( $ary_fields ); $i++ ) {

                assert ( $ary_fields[ 0 ]  != 'SELECT' );
                assert( ! is_null( $ary_fields[ $i ] ) );
                assert ( ( strlen( trim( $ary_fields[ $i ] ) ) ) );
//???                $this->AddColumn( $ary_fields[$i] );

            }


          
            
            if ( $this->m_chr == '' ) {
            // Am Ende des Statements


            // falls verschiedene clauses nicht besetzt sind, also -1, dann diese neu berechnen auf die
            // Position (letzter besetzter clause + 1)

            if ( $this->m_limit_start == -1 ) {
                if ( $this->m_order_by_start != -1 ) $this->m_limit_start = $this->m_order_by_start + $this->m_order_by_len;
                elseif ( $this->m_having_start != -1 ) $this->m_limit_start = $this->m_having_start + $this->m_having_len;
                elseif ( $this->m_group_by_start != -1 ) $this->m_limit_start = $this->m_group_by_start + $this->m_group_by_len;
                elseif ( $this->m_where_start != -1 ) {$this->m_limit_start = $this->m_where_start + $this->m_where_len;}
                else { $this->m_limit_start = $this->m_table_start + $this->m_table_len; }

                $this->m_limit_len = 0;
            }

            // echo "<br>limit start = $this->m_limit_start und limit len = $this->m_limit_len";

            if ( $this->m_order_by_start == -1 ) {
                if ( $this->m_having_start != -1 ) $this->m_order_by_start = $this->m_having_start + $this->m_having_len;
                elseif ( $this->m_group_by_start != -1 ) $this->m_order_by_start = $this->m_group_by_start + $this->m_group_by_len;
                elseif ( $this->m_where_start != -1 ) $this->m_order_by_start = $this->m_where_start + $this->m_where_len;
                else $this->m_order_by_start = $this->m_table_start + $this->m_table_len;

                $this->m_order_by_len = 0;
            }

            if ( $this->m_having_start == -1 ) {
                if ( $this->m_group_by_start != -1 ) $this->m_having_start = $this->m_group_by_start + $this->m_group_by_len;
                elseif ( $this->m_where_start != -1 ) $this->m_having_start = $this->m_where_start + $this->m_where_len;
                else $this->m_having_start = $this->m_table_start + $this->m_table_len;

                $this->m_having_len = 0;
            }

            if ( $this->m_group_by_start == -1 ) {
                if ( $this->m_where_start != -1 ) $this->m_group_by_start = $this->m_where_start + $this->m_where_len;
                else $this->m_group_by_start = $this->m_table_start + $this->m_table_len;

                $this->m_group_by_len = 0;
            }

            if ( $this->m_where_start == -1 ) {
                $this->m_where_start = $this->m_table_start + $this->m_table_len;

                $this->m_where_len = 0;
            }

            }



            // Debugging

            if ( false ) {

                echo "<br> von ScanStatement() eingelesenes statement = " . $this->GetStatementHTML( );

                echo "<br> erkannte Joins=";
                cDebugUtilities::PrintArray( 'erkannte Joins', $this->m_a_joins );

                echo "<br> erkannte Join-Tabellen=";
                cDebugUtilities::PrintArray( 'erkannte Join-Tabellen', $this->m_a_join_tables );

                echo "<br> erkannte Spalten=";
                $a_fld = array( );
                $this->GetFields( $a_fld );
                cDebugUtilities::PrintArray( 'erkannte Spalten', $a_fld );

                echo "<br> erkannte Tabellen=";
                cDebugUtilities::PrintArray( 'erkannte Tabellen', $this->m_a_tables );

                cDebugUtilities::PrintArray( 'Gefundene Feld-Aliases:', $this->m_a_field_aliases );

                echo "<br> Ermittelte Feldnamen =";
                for ( $i = 0; $i < $this->GetFieldCount( ); $i++ ) {

                    echo "<br>" . $this->GetField( $i );

                }
                echo "<br> Ermittelte Tabellennamen =";
                for ( $i = 0; $i < $this->GetTableCount( ); $i++ ) {

                    echo "<br>" . $this->GetTableDeclaration( $i ) ;

                }

            }

        } else {

            throw new \Exception(
            "<br> cSqlStatementGenerator not programmed for sql queries like " .
            $sql_statement .
            ' - '  .
            ' (' .
            debug_backtrace()[1]['function'] .
            ' /' .
            debug_backtrace()[0]['line'] .
            ')' .
            ' (' .
            debug_backtrace()[2]['function'] .
            ' /' .
            debug_backtrace()[1]['line'] .
            ')'
            );

        }

        //  echo "<br><h2>  ScanStatement() beendet</h2>";

    }	// function ScanStatement( )

    
    /**
      *
      * The method ArrayClean( ) trims all elements of the array $ary
      *
      * Example:
      *
      * @param array $ary the array to trim
      *
      */     

    private function ArrayClean( array & $ary ) {

        for ( $i = 0; $i < count( $ary ); $i++ ) {
            $ary[ $i ] = trim( $ary[ $i ] );
        }


    }   // function ArrayClean( )
    
    
    /**
      *
      * The method NextCh( ) returns the next character from the parsed statement when parsing the query without incrementing the internal counter $m_char_index
      *
      * Example:
      *
      * @return string the next character
      *
      */     
    

	private function NextCh( ) : string {

	    $ret = '';

	    if ( $this->m_chr != '' ) {

            $old = $this->m_chr;
            $old_pos = $this->m_char_index;

            $ret = $this->GetCh( );
            // $this->UnGetCh( );

            $this->m_char_index = $old_pos;
            $this->m_chr = $old;

            if( $old != $this->m_chr ) {
                echo "<br> NextCh: old = '$old' und m_chr = '{$this->m_chr}' !";
            }

	    }

	    return $ret;

	}	// function NextCh( )


    /**
      *
      * The method UngetCh( ) decrements the internal counter $m_char_index and sets $m_chr to the new character from the query
      *
      * Example:
      *
      */     
	
	
	private function UnGetCh( ) {

	    $this->m_char_index--;
	    $this->m_chr =  substr( $this->m_statement, $this->m_char_index, 1 ) ;

	    // echo "<br>\t\t\t\t\t UnGetCh( )!" . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';
	    // echo "<br>\t\t\t\t\t act chr = '$this->m_chr' index = $this->m_char_index";

	}	// function UnGetCh( )
	
    /**
      *
      * The method RewindTo( ) sets the internal counter $m_char_index to $index and sets $m_chr to the new character from the query
      *
      * Example:
      *
      */     
	

	private function RewindTo( int $index ) {



	    $this->m_char_index = $index;
	    $this->m_chr =  substr( $this->m_statement, $this->m_char_index, 1 ) ;


	    // echo "<br>\t\t\t\t\t RewindTo( ) in " . debug_backtrace()[1]['function']  . "!";
	    // echo "<br>\t\t\t\t\t act chr = '$this->m_chr' index = $this->m_char_index";

	}	// function UnGetCh( )

	
    /**
      *
      * The method GetCh( ) increments the internal counter $m_char_index and sets $m_chr to the new character from the query
      *
      * Example:
      *
      */     
	

	private function GetCh( ) : string {

	  if ( $this->m_char_index <= strlen( $this->m_statement ) ) {

		$this->m_char_index++;

		$this->m_chr =  substr( $this->m_statement, $this->m_char_index, 1 ) ;

		if ( false ) {
		   echo "<br>\t\t\t\t act chr = '$this->m_chr' index = $this->m_char_index";
		   // echo ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';
		   if ( $this->m_chr == '' ) 	    echo "<br>\t\t\t\t\t END OF STRING in statement!";
		}

		return $this->m_chr;

	    } else {


            return '';

	    }


	}	// function GetCh( )
	
    /**
      *
      * The method is_ctype_identifier( ) returns true, if $chr is a valid character for identifiers
      * The method takes into consideration, too, whether $chr is an extra identifier
      *
      * Example:
      *
      * @param string $chr is the character to test
      * @return bool true, if $chr is a valid character for identifiers
      *
      */     
	

	private function is_ctype_identifier( string $chr ) : bool {
	
        if ( $chr == '' ) return false;

	    return ( $chr == '_' ) || ( ctype_alnum( $chr ) || $this->IsExtraIdentifier( $chr ) || $this->is_ctype_sonderzeichen( $chr ) ) ;

	}	// function is_ctype_identifier( )
	
    /**
      *
      * The method is_ctype_sonderzeichen( ) returns true, if $chr is a valid character for identifiers and a country-specific character
      *
      * Example:
      *
      * @param string $chr is the character to test
      * @return bool true, if $chr is a valid character for identifiers
      *
      */    	
	
	private function is_ctype_sonderzeichen( string $chr ) : bool {
	
            return ( strpos ( 'äöüßÄÖÜ', $chr ) !== false );	
	
	}
	
	
    /**
      *
      * The method is_ctype_dbfield( ) returns true, if $chr is a valid character for database fields
      *
      * Example:
      *
      * @param string $chr is the character to test
      * @return bool true, if $chr is a valid character for a database field
      *
      */     	

	private function is_ctype_dbfield( string $chr ) : bool {
	    // mit dem Schema und oder Tabellennamen
	    return ( $chr == '.' ) || ( $chr == '_' ) || ( ctype_alnum( $chr ) || $this->is_ctype_sonderzeichen( $chr )  ) ;

	}	// function is_ctype_identifier( )


    /**
      *
      * The method is_ctype_identifier_start( ) returns true, if $chr is a valid starting character for identifiers
      * The method takes into consideration, too, whether $chr is an extra start identifier
      *
      * Example:
      *
      * @param string $chr is the character to test
      * @return bool true, if $chr is a valid starting character for identifiers
      *
      */     
	
	
	private function is_ctype_identifier_start( string $chr ) : bool {

	    if ( $chr == '' ) return false;

	    return ( $chr == '_' ) || ( ctype_alpha( $chr ) ) || $this->IsExtraStartIdentifier( $chr ) || $this->is_ctype_sonderzeichen( $chr ) ;

	}	// function is_ctype_identifier_start( )
	
	
	
    /**
      *
      * The method SkipSpaces( ) skips any spaces in the actual query string when scanning the statement
      *
      * Example:
      *
      * @return int the number of skipped characters
      *
      */     



	private function SkipSpaces( ) : int {

	// überspringe Leerzeichen ( Leerzeichen, TAB, LF, CRLF )

/*
bool ctype_space ( string $text )
Prüft ob jedes Zeichen in text irgendeine Art Leerzeichen erzeugt. Neben dem eigentlichen Leerzeichen schließt das
auch TAB, Zeilenvorschub, Carriage Return und Formularvorschub mit ein.
*/


	    $count = 0;

	    if ( ( $this->m_chr != '' ) && ( ctype_space( $this->m_chr ) ) ) {

            $count++;

            while ( ( ctype_space ( $chr = $this->GetCh( ) ) ) && ( $chr != '' )   ) {
                $count++;
            }

	    }

	    return $count;


	}	// function SkipSpaces( )
	
	
    /**
      *
      * The method ScanIdentifier( ) scans the actual query string and returns the next identifier
      *
      * Example:
      *
      * @param boolean $begradigen If true, then return the identifier in uppercase characters. Defaults to false.
      * @param boolean $punkteinlesen If true, then qualifying points ('.') are valid characters for the identifier. Defaults to false.
      * @param boolean $scan_for_alias If true, then a trailing alias will returned as well. Defaults to true. Not programmed yet!
      * @return string the identifier or an empty string
      *
      */     

	

	private function ScanIdentifier( bool $begradigen = false, bool $punkteinlesen = false, bool $scan_for_alias = true  ) : string {
	
        // TODO: scan_for_alias ausprogrammieren!

	    // begradigen meint strtoupper und trim

	    // echo "<br> " . debug_backtrace()[1]['function'] . ' line=' . debug_backtrace()[0]['line'] . " scannt nach Identifier";

	    $id = '';

	    if ( $this->IsNumberStart( ) ) {

            $pos = $this->m_char_index;
            $this->SkipSpaces( );
            if ( ctype_digit( $this->m_chr ) ) {
                return $this->ScanNumber( );
            }

            $this->RewindTo( $pos );

	    }

	    if ( $this->is_ctype_identifier_start( $this->m_chr ) )  {

            $id .= $this->m_chr;

            while ( ( ( $this->is_ctype_identifier( $chr = $this->GetCh( ) ) ) ||
                ( ( $punkteinlesen ) && ( $this->is_ctype_dbfield( $chr  ) ) ) ) &&
                ( $chr != ',' ) &&
                ( $chr != ';' ) )
                {

                $id .= $chr;

            }

	    } elseif ( $this->m_chr == "'" )  {

            $id .= "'";
            $this->GetCh( );
            $id .= $this->ScanUntilFolgezeichen( "'" );

	    } elseif ( $this->m_chr == '"' )  {

            $id .= '"';
            $this->GetCh( );
            $id .= $this->ScanUntilFolgezeichen( '"' );

	    } elseif ( $this->m_chr == '`' )  {

            $id .= '`';
            $this->GetCh( );
            $id .= $this->ScanUntilFolgezeichen( '`' );

	    }

	    // echo "<br> neuer Identifier ->$id<-";

	    if ( $begradigen ) {
            $id = strtoupper( trim( $id ) );
	    }

	    return $id;

	}	// function ScanIdentifier( )
	
    /**
      *
      * The method ScanIdentifier( ) scans the actual query string and returns the part of the query which ends with the character $zeichen from $m_char_index on
      *
      * Example:
      *
      * @param string $zeichen the terminating character to search for
      * @return string the part which ends with the character $zeichen without the terminating character or an empty string
      *
      */    	

	private function ScanUntilFolgezeichen( string $zeichen ) : string {

	    $content = '';
	    
	    if ( $zeichen == '' ) return '';
	    if ( $this->m_chr == '' ) return '';

	    assert( strlen( $zeichen ) > 0 );

	    if ( ( strlen( $zeichen ) ) && ( $this->m_chr != '' ) )  {

            while ( ( ( $chr = $this->m_chr) != $zeichen ) && ( $chr != '' ) ) {

                $content .= $chr;
                $this->GetCh( );

            }

            $content .= $zeichen;
            assert( $this->m_chr == $zeichen );
            $this->GetCh( );
            assert( $this->m_chr != $zeichen );

	    }



	    // echo "<br> Inhaltbis zum Zeichen '$zeichen' = '$content'";

	    return $content;

	}	// function ScanUntilFolgezeichen( )
	
	
    /**
      *
      * The method is_ctype_number_start( ) returns true, if $chr is a valid starting character for a sql number (dec, hex, bin)
      *
      * Example:
      *
      * @param string $chr is the character to test
      * @return bool true, if $chr is a valid starting character for a sql number
      *
      */     
	


      private function is_ctype_number_start( string $chr ) : bool {

        if ( $chr == '' ) return false;
        return strpos( '+-0123456789.bBxX', $chr ) !== false;

      }	// function is_ctype_number_start
      
    /**
      *
      * The method is_ctype_number( ) returns true, if $chr is a valid character for the body of a sql number (dec, hex, bin)
      *
      * Example:
      *
      * @param string $chr is the character to test
      * @return bool true, if $chr is a valid character for a sql number
      *
      */     
      

      private function is_ctype_number( string $chr ) : bool {

        if ( $chr == '' ) return false;
        return strpos( '0123456789eE.', $chr ) !== false;

      }	// function is_ctype_number
      
      
    /**
      *
      * The method IsNumberStart( ) returns true, if there is the starting char of a (dec, hex, bin) sql number in the buffer
      *
      * Example:
      *
      * @return bool true, if $chr is a valid character for a sql number
      *
      */     


      private function IsNumberStart( ) : bool {

            // steht eine gewöhnliche Zahl oder eine hexadezimale oder binäre Zahl im Eingabepuffer? ?
            
            if ( $this->m_chr == '' )  return false;

            $ch = $this->m_chr;
            $next_ch = $this->NextCh( );

            $ret = ( ctype_digit( $this->m_chr ) ) ||
                ( $this->is_ctype_number_start ( $this->m_chr )   ) ||
                ( ( $this->m_chr == '.' ) && ( ctype_digit( $this->NextCh( ) ) ) ) ||
                ( ( $this->m_chr == 'b' || $this->m_chr == 'B' ) && ( ( $next_ch == '"' ) || $next_ch == "'" || $next_ch == "`" ) ) ||
                ( ( $this->m_chr == 'x' || $this->m_chr == 'X' ) && ( ( $next_ch == '"' ) || $next_ch == "'" || $next_ch == "`" ) )
                ;

            if ( strpos( 'bBxX', $this->m_chr ) !== false ) {

                $ret = ( strpos( "'\"`", $next_ch ) !== false );

            }

            // Quatsch ? assert( $ch == $this->m_chr );

            return $ret;

      } 	// function IsNumberStart( )
      
      
    /**
      *
      * The method StartsBinary( ) returns true, if there is a binary sql number in the buffer
      *
      * Example:
      *
      * @return bool true, if there is a binary sql number in the buffer
      *
      */     
      

      private function StartsBinary( ) : bool {

        return ( ( strtoupper( $this->m_chr ) == 'B' ) && ( ( $this->NextCh( ) == '"' ) || $this->NextCh( ) == "'" || $this->NextCh( ) == "`" ) );

      }	// function StartsBinary( )

    /**
      *
      * The method StartsHex( ) returns true, if there is a hexadecimal sql number in the buffer
      *
      * Example:
      *
      * @return bool true, if there is a hexadecimal sql number in the buffer
      *
      */     
      

      private function StartsHex( ) : bool {

        return ( ( strtoupper( $this->m_chr ) == 'X' ) && ( ( $this->NextCh( ) == '"' ) || $this->NextCh( ) == "'" || $this->NextCh( ) == "`" ) );

      }	// function StartsHex( )
      
      
    /**
      *
      * The method ScanNumber( ) scans the actual query string and returns the next sql number which is in the buffer      
      *
      * Example:
      *
      * @return string the number or an empty string
      *
      */     


	private function ScanNumber( ) : string {

	    // echo "<br> " . debug_backtrace()[1]['function'] . ' line=' . debug_backtrace()[0]['line'] . " scannt nach Zahl in ScanNumber( )";

	    /*

X'01AF'
X'01af'
x'01AF'
x'01af'
0x01AF
0x01af

Legal bit-value literals:

b'01'
B'01'
0b01

	    */

	    $id = '';
	    $next_ch = $this->NextCh( );

	    // echo "\n ScanNumber:  this->m_chr = '$this->m_chr'  next_ch = '$next_ch'";
	    // $this->DumpState( );


	    if ( ( $this->m_chr == 'b' || $this->m_chr == 'B' ) && ( ( $next_ch == '"' ) || $next_ch == "'" || $next_ch == "`" ) ) {

            $id .= 'b';
            $zeichen = $this->GetCh( );
            $id .= $zeichen;
            $this->GetCh();
            $id .= $this->ScanUntilFolgezeichen( $zeichen);
    // 		$id .= $zeichen;
    // 		$this->GetCh( );

	    } elseif ( ( $this->m_chr == 'x' || $this->m_chr == 'X' ) && ( ( $next_ch == '"' ) || $next_ch == "'" || $next_ch == "`" ) ) {
            $id .= 'x';
            $zeichen = $this->GetCh( );
            $id .= $zeichen;
            $this->GetCh();
            $id .= $this->ScanUntilFolgezeichen( $zeichen);
    // 		$id .= $zeichen;
    // 		$this->GetCh( );

	    } elseif ( ( $this->m_chr == '.' ) && ( ctype_digit( $next_ch ) ) ) {

            $id .= '.';
            // $this->GetCh( );

            while ( ( ( $this->is_ctype_number( $chr = $this->GetCh( ) ) )  ) && ( $chr != '' ) ) {
                $id .= $chr;
            }

	    }  elseif ( $this->is_ctype_number_start( $this->m_chr ) )  {

            $id .= $this->m_chr;
            $this->GetCh( );

            if ( ( $id == '+' ) || ( $id == '-' )  ) {

                $id .= $this->ScanNumber( );

            } else {

                $this->UnGetCh( );

                while ( ( ( $this->is_ctype_number( $chr = $this->GetCh( ) ) )  ) && ( $chr != '' ) ) {
                $id .= $chr;
                }

            }

	    } elseif ( $this->m_chr == "'" )  {

            $id = "'";
            $this->GetCh( );
            $id .= $this->ScanUntilFolgezeichen( "'" );
    // 		$this->GetCh( );
    // 		$id .= "'";

	    } elseif ( $this->m_chr == '"' )  {

            $id = '"';

            $this->GetCh( );
            $id .= $this->ScanUntilFolgezeichen( '"' );
    // 		$this->GetCh( );
    // 		$id .= '"';

	    } elseif ( $this->m_chr == '`' )  {

            $id = '"';

            $this->GetCh( );
            $id .= $this->ScanUntilFolgezeichen( '`' );
    // 		$this->GetCh( );
    // 		$id .= '"';

	    } else {

		    while ( ( ctype_digit( $chr = $this->GetCh( ) )  || $chr == '.' ) && ( $chr != '' ) ) {
			$id .= $chr;
		    }

	    }

	    if ( false ) {
            echo "<br> ScanNumber( ) liefert Zahl '$id' an " . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';		  ;
            $this->DumpStatementRest( );
            echo "\n $this->m_statement";
	    }

	    return $id;

	}	// function ScanNumber( )

    /**
      *
      * The method ScanNumber( ) scans the actual query string and returns the next subquery which is in the buffer      
      *
      * Example:
      *
      * @return string the subquery or an empty string
      *
      */     


      private function ScanSubQuery( ) : string {

        // bis zur schließenden Klammer einlesen
        // dabei Stringkonstanten und öfnende Funktionsklammern zählen und überspringen

        // echo "<br> untersuche Subquery-Statement";

        $startpos = $this->m_char_index;

        $klammerebene = 1;

        $fertig = false;
        $statement = '';

        $this->SkipSpaces( );

        while( ! $fertig ) {

            if ( $this->m_chr == "'" ) {

            $this->GetCh( );
            $statement .= "'" . $this->ScanUntilFolgezeichen( "'" );
    // 		  $this->GetCh( );
    // 		  $statement .= "'";
            $this->SkipSpaces( );

            } elseif ( $this->m_chr == '"' ) {
            $this->GetCh( );
            $statement .= '"' . $this->ScanUntilFolgezeichen( '"' );
    // 		  $this->GetCh( );
    // 		  $statement .= '"';
            $this->SkipSpaces( );

            } elseif ( $this->m_chr == '`' ) {
            $this->GetCh( );
            $statement .= '"' . $this->ScanUntilFolgezeichen( '`' );
    // 		  $this->GetCh( );
    // 		  $statement .= '"';
            $this->SkipSpaces( );

            } elseif ( $this->m_chr == '(' ) {

            $klammerebene++;
            $statement .= '(';
            $this->GetCh( );
            $this->SkipSpaces( );

            } elseif ( $this->m_chr == ')' ) {

            $klammerebene--;
            $statement .= ')';
            $this->GetCh( );
            $this->SkipSpaces( );

            }  elseif ( $this->is_ctype_identifier_start( $this->m_chr )  ) {

            $identifier = $this->ScanIdentifier( );
            $statement .= $identifier . ' ';
            $this->SkipSpaces( );

            } else {

            // die ( "<br> ScanSubQuery( ) unknown condition parsing sql subquery statement" . __LINE__  );
            $statement .= $this->m_chr;
            $this->GetCh( );

            }

            $fertig = ( $klammerebene <= 0 ) || ( $this->m_chr == '' ) || ( $this->m_chr == ';' );
            // echo "<br> klammerebene = $klammerebene";


        }

        $statement = substr( $this->m_statement, $startpos, $this->m_char_index - $startpos  );	// abschließende Klammer nicht berücksichtigen
        // echo "<br> Subquery-Statement abgeschlossen mit '$statement'";

        return $statement;

      }	// function ScanSubQuery( )
      
    /**
      *
      * The method ScanPartitionlist( ) scans the actual query string and returns the next partition list which is in the buffer      
      *
      * Example:
      *
      * @return string the partition list or an empty string
      *
      */     
      

      private function ScanPartitionlist( ) : string {

        // echo "<br> ScanPartitionlist( )";

        $list = '';

        $this->SkipSpaces( );
        assert( $this->m_chr == '(' );

        if ( $this->m_chr == '('  ) {

            $list .= '(';

            $fertig = false;
            while ( ! $fertig ) {

            $list .= $this->GetCh( );
            $fertig = ( ( $this->m_chr == ')' ) || ( $this->m_chr == '' ) );

            }
            if ( $this->m_chr != '' ) {
            // $list .= ')';
                $this->GetCh( );
            } else {
                throw new \Exception( "<br> incomplete PARTITION STATEMENT" );
            }

        }

        // echo "<br> Beende ScanPartitionlist( ) mit $list";

        return $list;

      }	// function ScanPartitionlist( )

    /**
      *
      * The method ScanOperator( ) scans the actual query string and returns the next SQL operator which is in the buffer      
      *
      * Example:
      *
      * @return string the operator or an empty string
      *
      */         
      

      private function ScanOperator( ) : string {

	  // folgt ein Operator?

         // echo "<br> " . debug_backtrace()[1]['function'] . ' line=' . debug_backtrace()[0]['line'] . " scannt nach Operator in ScanOperator( )";
         
     if ( $this->m_chr == '' ) return '';

	  $pos = $this->m_char_index;
	  $found = false;

	  $this->SkipSpaces( );

	  $operator = '';

//	  $chr = $this->GetCh( );
	$chr = $this->m_chr;

	  if ( strpos( '!+-*/><|=&~^', $this->m_chr ) !== false ) {
	      $found = true;
	      $operator = $this->m_chr;
 	      $this->GetCh( );

	      if ( $operator == '<' && $this->m_chr == '=' ) {
		  $operator .= $this->GetCh( );

		  if ( ( $this->m_chr ) == '>' ) {

		      $operator .= $this->GetCh( );

		  }

	      } elseif ( $operator == '>' && $this->m_chr == '=' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '<' && $this->m_chr == '=' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '-' && $this->m_chr == '>' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '<' && $this->m_chr == '<' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '<' && $this->m_chr == '=' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '!' && $this->m_chr == '=' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '<' && $this->m_chr == '>' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '>' && $this->m_chr == '>' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '|' && $this->m_chr == '|' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      } elseif ( $operator == '>' && $this->m_chr == '>' ) {
		  $operator .= $this->m_chr;
		  $this->GetCh( );
	      }

	  } else {

	      $token = strtoupper( $this->NextToken( ) );

	      if (
		  $token == 'DIV' ||
		  $token == 'MOD' ||
		  $token == 'AND' ||
		  $token == 'OR' ||
		  $token == 'DIV' ||
		  $token == 'IS' ||
		  $token == 'LIKE' ||
		  $token == 'NOT' ||
		  $token == 'RLIKE' ||
		  $token == 'REGEXP' ||
		  $token == 'RLIKE' ||
		  $token == 'SOUNDS' ||
		  $token == 'BINARY' ||
		  $token == 'CASE' ||
		  $token == 'XOR'

		  ) {
		      $found = true;
		      $operator = $this->ScanIdentifier( );
		      $this->SkipSpaces( );

		      if ( $operator == 'IS' ) {

			  // IS, IS NOT, IS NOT NULL, IS NULL

			  $token = strtoupper( $this->ScanIdentifier( ) );
			  $operator .= ' ' . $token;
			  $this->SkipSpaces( );

			  if ( ( $token == 'NOT' ) && ( $this->NextToken( ) == 'NULL' ) ) {

			      $token = strtoupper( $this->ScanIdentifier( ) );
			      $operator .= ' ' . $token;
			      $this->SkipSpaces( );

			  }

		      } elseif ( $operator == 'NOT' ) {

			  // NOT BETWEEN .. AND ..

			  $token = strtoupper( $this->ScanIdentifier( ) );
			  $operator .= ' ' . $token;
			  $this->SkipSpaces( );

			  if  ( $token == 'BETWEEN' )  {

			      $token = strtoupper( $this->ScanTableOrFieldName( ) );
			      $operator .= ' ' . $token;
			      $this->SkipSpaces( );

			  } elseif  ( $token == 'LIKE' )  {

			      $token = strtoupper( $this->ScanTableOrFieldName( ) );
			      $operator .= ' ' . $token;
			      $this->SkipSpaces( );

			  } elseif  ( $token == 'REGEXP' )  {

			      $token = strtoupper( $this->ScanTableOrFieldName( ) );
			      $operator .= ' ' . $token;
			      $this->SkipSpaces( );

			  }

		      } elseif ( $operator == 'SOUNDS' ) {

			  // IS, IS NOT, IS NOT NULL, IS NULL

			  $token = strtoupper( $this->ScanIdentifier( ) );
			  $operator .= ' ' . $token;
			  $this->SkipSpaces( );

			  if ( $token == 'LIKE' )  {

			      $token = strtoupper( $this->ScanIdentifier( ) );
			      $operator .= ' ' . $token;
			      $this->SkipSpaces( );

			  }



		      } elseif ( $operator == 'CASE' ) {

			  // geht bis 'END'

			  // $token = strtoupper( $this->ScanIdentifier( ) );
			  // $operator .= ' ' . $token . ' ';
			  $this->SkipSpaces( );
			  $operator .= $this->ScanCaseStatement( );
		      }

		  }

	  }

	  if ( ! $found ) $this->RewindTo( $pos );

	  // echo "<br> Beende ScanOperator( ) mit '$operator'   m_chr = '$this->m_chr'";

	  return $operator;

      }	// function ScanOperator( )
      
      
    /**
      *
      * The method in_array_icase( ) is a case insensitive in_array( )
      *
      * Example:
      *
      * @param string $needle the string to search for 
      * @param array $a_haystack the array of strings to be searched for $needle
      * @return bool true, if $needle was found in $a_haystack
      *
      */         
      

      private function in_array_icase( $needle, $a_haystack ) : bool {

        for ( $i = 0; $i < count( $a_haystack ); $i++ ) {

            if ( strtoupper( $needle ) == strtoupper( $a_haystack[ $i ] ) ) return true;

        }

        return false;

      }	// function in_array_icase( )
      
      
      
    /**
      *
      * The method InArray2( ) is a case insensitive in_array( )
      *
      * Example:
      *
      * @param array $a_needles the array with strings to search for in $a_haystack
      * @param array $a_haystack the array of strings to be searched for strings out of $a_needles
      * @param bool $icase whether the search should be case insensitive or not. It defaults to false
      * @return bool true, if one string in $a_needles was found in $a_haystack
      *
      */         


      private function InArray2( $a_needles, $a_haystack, $icase = false ) : bool {

        for ( $i = 0; $i < count( $a_needles ); $i++ ) {

            $needle = $a_needles[ $i ];

            $found = false;
            if ( $icase ) {

            for ( $j = 0; $j < count( $a_haystack ); $j++ ) {

                $found = ( strtoupper( $needle ) == strtoupper( $a_haystack[ $j ] ) );

            }

            } else {
                $found = in_array( $neelde, $a_haystack );
            }

            if ( $found ) return true;

        }

        return false;

      }	// function InArray2( )

      
    /**
      *
      * The method ScanCaseStatement( ) scans the actual query string and returns the next SQL CASE statement which is in the buffer      
      *
      * Example:
      *
      * @return string the case statement or an empty string
      *
      */         
      
      
      
      private function ScanCaseStatement( ) : string {

      // CASE ALLOWANCE WHEN 0 THEN 'none' WHEN 1 THEN 'read' WHEN 2 THEN 'write' ELSE ALLOWANCE END

      /*

      CASE Syntax

      CASE case_value
	  WHEN when_value THEN statement_list
	  [WHEN when_value THEN statement_list] ...
	  [ELSE statement_list]
      END CASE

      Or:

      CASE
	  WHEN search_condition THEN statement_list
	  [WHEN search_condition THEN statement_list] ...
	  [ELSE statement_list]
      END CASE


      */

	  // echo "<br> ScanCaseStatement( ) " . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';;

// echo "<br> ScanCaseStatement( ) " . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';;


	  // $this->DumpState( );

	  $this->m_in_case_statement = true;

	  $expr = $this->ScanConditionalExpression( array( 'END' ) );

	  $expr .= ' ' . $this->ScanIdentifier( );	// skip "END"

//	  echo "<br> case found condition : " . $expr;
	  $skipped = $this->SkipSpaces( );

	  $this->m_in_case_statement = false;

// die("<br> Abbruch " . __LINE__ );

	  return $expr;


      }	// function ScanCaseStatement( )
      
      
    /**
      *
      * The method IsUnaryOperator( ) returns true, if $operator is an SQL unary operator
      *
      * Example:
      *
      * @return bool true, if $operator is an SQL unary operator
      *
      */     
      

      private function IsUnaryOperator( $operator ) : bool {


	  return (  $operator == '!'  ||
		    $operator == 'NOT'  ||
		    $operator == 'IS NOT NULL'  ||
		    $operator == 'IS NULL'  ||
		    $operator == 'BINARY'  ||
		    $operator == '~'  ||
		    $operator == '-'

		      );

      }
      
    /**
      *
      * The method FollowsOperator( ) returns true, if there starts an operator in the actual buffer
      *
      * Example:
      *
      * @return bool true, if there starts an operator in the actual buffer
      *
      */     
      

      private function FollowsOperator( ) : bool {

        $pos = $this->m_char_index;

        $ret = ( ( strlen( $this->ScanOperator( ) ) ) > 0 );

        $this->RewindTo( $pos );

        return $ret;

      }
/*
      private function IsIndexHintStart( $keyword ) {

	  $keyword = strtoupper( $keyword );

	  return (   $keyword == 'FORCE' ) ||
		     $keyword == 'IGNORE' ) ||
		     $keyword == 'USE'
		     );

      }	// function IsIndexHintStart( )
*/

    /**
      *
      * The method ScanConditionalExpression( ) scans the actual query string and returns the next conditional expression which is in the buffer      
      *
      * Example:
      *
      * @return string the conditional expression or an empty string
      *
      */         



      private function ScanConditionalExpression( array $a_stop_tokens = array( ) ) : string {

	   // x = 15 or t > 8 and not substr( 8 ) or 15

	  // echo "<br> ScanConditionalExpression( ) " . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';;

	  // $this->DumpState( );
	  $expr = '';
	  $skipped = $this->SkipSpaces( );
	  $klammer_ebene = 0;

	  $fertig = false;
	  while ( ! $fertig ) {

	      if ( $this->FollowsOperator( ) ) {

		  $token = $this->ScanOperator( );

	      /*} elseif ( $this->m_chr == '(') {

		  echo "<br> öffnende Klammer - also keine Suche";
	      */
	      } else {
		  $token = $this->ScanTableOrFieldName( );
	      }

	      //  echo "<br> ScanConditionalExpression( ) mit Token '$token' und m_chr = $this->m_chr";

	      if ( ( ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( $token ) ) ||  ( \rstoetter\libsqlphp\cMysql57_Utils::IsJoinSyntax( $token ) ) ) &&
	           ( ! \rstoetter\libsqlphp\cMysql57_Utils::IsIndexHintStart( $token ) ) ){

		  if ( \rstoetter\libsqlphp\cMysql57_Utils::IsJoinSyntax( $token ) ) {

			// echo "<br> Join detected in '$token'";

		      if ( ( strtoupper( $token ) == 'LEFT' ) || ( strtoupper( $token ) == 'RIGHT' ) ) {

			  if ( $this->m_chr != '(' ) {
			      $fertig = true;
			      for ( $i = 0; $i < strlen( $token ); $i++ ) {
				  $this->UngetCh( );
			      }
			  }


		      }

		  } else {

		      $fertig = true;
		      for ( $i = 0; $i < strlen( $token ); $i++ ) {
			  $this->UngetCh( );
		      }
		  }
	      }

	      if  ( $this->in_array_icase( $token, $a_stop_tokens, $icase = true ) ) {

		  for ( $i = 0; $i < strlen( $token ); $i++ ) {
			    $this->UngetCh( );
		  }

		  $fertig = true;


	      }

	      $skipped = $this->SkipSpaces( );

	      if ( ! $fertig ) {

		  if ( strlen( $token ) ) {

		      if ( $skipped ) $expr .= ' ';
		      $expr .= ' ' . $token;
		      if ( $this->SkipSpaces( ) ) { $expr .= ' '; }


		  } elseif ( ( $this->m_chr == '' ) || ( $this->m_chr == ';' ) ) {

		      $fertig = true;
		      $expr .= $token;
		      if ( $skipped ) $expr .= ' ';

		  } elseif ( $this->m_chr == '{' ) {

		      $expr .= '{';
		      $this->GetCh( );

		      if ( $this->SkipSpaces( ) ) $expr .= ' ';
		      $expr .= $this->ScanUntilFolgezeichen( '}' );
		      assert( $this->m_chr != '}');
		      if ( $this->SkipSpaces( ) ) $expr .= ' ';

		  } elseif ( $this->m_chr == '"' ) {

		      $expr .= '"';
		      $this->GetCh( );

		      if ( $this->SkipSpaces( ) ) $expr .= ' ';
		      $expr .= $this->ScanUntilFolgezeichen( '"' );
		      if ( $this->SkipSpaces( ) ) $expr .= ' ';

		  } elseif ( $this->m_chr == "'" ) {

		      $expr .= "'";
		      $this->GetCh( );

		      if ( $this->SkipSpaces( ) ) $expr .= ' ';
		      $expr .= $this->ScanUntilFolgezeichen( "'" );
		      if ( $this->SkipSpaces( ) ) $expr .= ' ';

		  } elseif ( $this->m_chr == '(' ) {

		      // echo "<br> ScanConditionalExpression() bei öffnender Klammer mit NextToken() =" . $this->NextToken( );
		      // echo "<br> token = '$token'";

		      $expr .= '(';
		      $this->GetCh( );

		      if ( strtoupper( $this->NextToken( ) ) == 'SELECT' ) {

			  $expr .= $this->ScanSubQuery( );

		      } else {

			  $klammer_ebene++;

		      }

		      if ( $this->SkipSpaces( ) ) $expr .= ' ';

		  } elseif ( $this->m_chr == ')' ) {

		      $expr .= ')';
		      $this->GetCh( );

		      $klammer_ebene--;
		      if ( $this->SkipSpaces( ) ) $expr .= ' ';

		  } elseif ( $this->m_chr == ',' || $this->m_chr == '=' ) {

		      $expr .= $token;
		      if ( $skipped ) $expr .= ' ';

		      $expr .= $this->m_chr;
		      $this->GetCh( );

		      if ( $this->SkipSpaces( ) ) $expr .= ' ';

		  } else {

		      if ( ( $token == '' && ( $this->m_chr == '' || $this->m_chr == ';' ) ) ) {
			  $fertig = true;
			  // echo "<br> fertig weil am Ende angelangt";
		      } else {
			  // echo "<br>ScanConditionalExpression( )-> Token = '$token' und m_chr = '$this->m_chr'";
			  $expr .= ' ' . $token . ' ';
			  if ( ( $skipped ) || ( $this->SkipSpaces( ) ) ) $expr .= ' ';

			  // TODO: stimmen die beiden Zeilen wirklich immer?
			  $this->UngetCh( );
			  if ( $klammer_ebene == 0 ) $fertig = true;
		      }
		  }
	      }

	  }	// while

 	  if ( false ) {
	      echo "<br> ScanConditionalExpression( ) liefert --[$expr]-- bei statement = '$this->m_statement'";
	      echo "<br> ScanConditionalExpression( ) Klammerung = $klammer_ebene";
	  }

	  // echo "<br> ScanConditionalExpression() endet mit token = '$token'";

	  if ( $this->m_in_where ) {

	      $this->m_where_part = $expr;

	  }

	  if ( $klammer_ebene > 0 ) {
	      $this->DumpState( );
	      // $this->Dump( );
	      throw new \Exception("<br> Fehler: ScanConditionalExpression() mit Klammerung $klammer_ebene und m_chr = '$this->m_chr' und token = '$token'");
	  }

// die( "<br> Abbruch: ScanConditionalExpression leaving with '$expr'");


	  return $expr;

      } // private function ScanConditionalExpression( )
      
      
    /**
      *
      * The method ScanJoinCondition( ) scans the actual query string and returns the next join condition which is in the buffer      
      *
      * Example:
      *
      * @return string the join condition or an empty string
      *
      */         


      private function ScanJoinCondition( string $prevtoken ) : string {

	// join_condition:
	// ON conditional_expr
	// | USING (column_list)

	  // echo "<br> ScanJoinCondition( )";

	  $cond = '';

	  $this->SkipSpaces( );

	  if ( $prevtoken == 'ON') {

	      $cond = $this->ScanConditionalExpression( );

	  } elseif ( $prevtoken == 'USING' ) {

	      assert( $this->m_chr == '(' );
	      $cond .= '(';
	      $this->GetCh( );
	      $this->SkipSpaces( );
	      $cond = $this->ScanUntilFolgezeichen(')');

	  }

	  // echo "<br> beende ScanJoinCondition( ) mit $cond";

	  return $cond;

      }	// function ScanJoinCondition( )
      
    /**
      *
      * The method ScanTableOrFieldName( ) scans the actual query string and returns the next table or field name which is in the buffer      
      *
      * Example:
      *
      * @return string the table or field name or an empty string
      *
      */         
      

      private function ScanTableOrFieldName( ) : string {

	  // kann mit ' oder " oder ` beginnen
	  // kann einen Punkt enthalten -> tablenem.fieldname oder schemaname.tablename.fieldname oder schemaname.tablename

	  $skipped = 0;
	  $id = '';

	  // echo "<br> ScanTableOrFieldName( ) " . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';;

	  $this->SkipSpaces( ) ;

	  $suffix = '';
	  if ( $this->m_chr == '"' ) {
	      $suffix = '"';
	      $this->GetCh();
	  } elseif ( $this->m_chr == "'" ) {
	      $suffix = "'";
	      $this->GetCh();
	  } elseif ( $this->m_chr == '`' ) {
	      $suffix = '`';
	      $this->GetCh();
	  }

	  // $this->SkipSpaces( );

	  if ( strlen( $suffix) ) {
	    $id .= $this->ScanUntilFolgezeichen( $suffix );
//   	    $this->GetCh( );
  	    $id = $suffix . $id ;

	  } else {

	    // ScanIdentifier( $begradigen = false, $punkteinlesen = false
// 	    $id .= $this->ScanIdentifier( false, true );
	    $id .= $this->ScanIdentifier( false, false );

	  }

	  $skipped += $this->SkipSpaces( );

	  if ( $this->m_chr == '.' ) {
	      $id .= '.';
	      $this->GetCh( );
	      $id .= $this->ScanTableOrFieldName( );
	      $skipped += $this->SkipSpaces( );
	  } else {

	      for ( $i = 0; $i < $skipped; $i ++ ) { $this->UngetCh( ); }

	  }

// 	  $ret = $suffix . $id . $suffix;

// 	  assert( strlen( $id ) );

	  // echo "<br> ScanTableOrFieldName( ) liefert ->'$id' m_chr = $this->m_chr<br>";
	  return $id;


      }	// function ScanTableOrFieldName
      
    /**
      *
      * The method ScanIndexHint( ) scans the actual query string and returns the next index hint which is in the buffer      
      *
      * Example:
      *
      * @return string the index hint or an empty string
      *
      */         
      

      private function ScanIndexHint( ) : string {

      // index_hint:
      // USE {INDEX|KEY}
      // 	[FOR {JOIN|ORDER BY|GROUP BY}] ([index_list])
      // | IGNORE {INDEX|KEY}
      //	[FOR {JOIN|ORDER BY|GROUP BY}] (index_list)
      // | FORCE {INDEX|KEY}
      //	[FOR {JOIN|ORDER BY|GROUP BY}] (index_list)

	  // echo "<br> ScanIndexHint";

	  $hint = '';

	  if ( $this->SkipSpaces( ) ) $hint .= ' ';


	  $identifier = $this->ScanIdentifier( true );

	  assert( $his->IsIndexHintStart( $identifier) );

	  if ( $his->IsIndexHintStart( $identifier) ) {

	      $hint .= $identifier;
	      if ( $this->SkipSpaces( ) ) $hint .= ' ';

	      if ( ( $identifier == 'USE') ||  ( $identifier == 'IGNORE') || ( $identifier == 'FORCE') ) {

		  $hint = $this->ScanIdentifier( true );
		  if ( $identifier == 'INDEX' OR $identifier == 'KEY' ) {

		      if ( $this->SkipSpaces( ) ) $hint .= ' ';
		      $hint .= $identifier;
		      $identifier = $this->ScanIdentifier( true );

		  }

		  if ( $identifier == 'FOR' ) {

		      if ( $this->SkipSpaces( ) ) $hint .= ' ';
		      $hint .= $identifier;
		      $identifier = $this->ScanIdentifier( true );

		  }

		  if ( ( $identifier == 'JOIN' ) || ( $identifier == 'ORDER' ) || ( $identifier == 'GROUP' ) ) {

		      if ( $this->SkipSpaces( ) ) $hint .= ' ';
		      $hint .= $identifier;

		      if ( $identifier == 'ORDER' || ( $identifier == 'GROUP' ) ) {

			  if ( $this->SkipSpaces( ) ) $hint .= ' ';
			  $identifier = $this->ScanIdentifier( true );
			  assert( $identifier == 'BY' );
			  $hint .= 'BY';

		      }

		      if ( $this->SkipSpaces( ) ) $hint .= ' ';
		      assert( $this->m_chr == '(');
		      $hint .= '(';
		      $this->GetCh( );

		      if ( $this->SkipSpaces( ) ) $hint .= ' ';
		      $hint .= $this->ScanUntilFolgezeichen(')');


		  }

	      } else {

		  die( "<br> error in index hint (use, force, ignore erwartet)" );

	      }

	  }

	  // echo "<br> ScanIndexHint liefert $hint";

	  return $hint;

      }	// function ScanIndexHint( )

      
    /**
      *
      * The method ScanIndexHintList( ) scans the actual query string and returns the next index hint list which is in the buffer      
      *
      * Example:
      *
      * @return string the index hint list or an empty string
      *
      */         
      
      
      private function ScanIndexHintList( ) : string {

        // index_hint_list:
        // index_hint [, index_hint] ...

        // echo "<br> ScanIndexHintList";

        $this->SkipSpaces( );

        $list = '';

        $identifier = $this->ScanIdentifier( true );
        if ( $his->IsIndexHintStart( $identifier) ) {

            for ( $i = 0; $i < strlen( $identifier); $i++ ) $this->UnGetCh( );
            $list .= $this->ScanIndexHint( );

            $fertig = false;
            while ( !$fertig ) {

            $this->SkipSpaces( );

            if ( $this->m_chr == ',') {
                $list .= ',';
                $this->GetCh( );
                $list .= $this->ScanIndexHint( );

            } else {

                $fertig = true;


            }


            if  ($this->m_chr == '' ) throw new \Exception("<br> error: incomplete index hint list");

            }

        }

        // echo "<br> ScanIndexHintList liefert->$list";

        return $list;

      }	// function ScanIndexHintList( )
      
      
    /**
      *
      * The method DumpStatementAbgearbeitet( ) dumps the scanned part of the query string
      *
      * Example:
      *
      */         

      private function DumpStatementAbgearbeitet( ) {

        $pos = $this->m_char_index - 1;
        echo "<br> Bearbeiteter Teil vom Statement bis Position {$pos} -><br>" . substr( $this->m_statement, 0, $this->m_char_index - 1 );
        echo ' ( ' . debug_backtrace()[1]['function'] . ' )';

      }	// function DumpStatementAbgearbeitet( )

    /**
      *
      * The method DumpStatementRest( ) dumps the part which is to be scanned of the query string
      *
      * Example:
      *
      */         
      

      private function DumpStatementRest( ) {

        $pos = $this->m_char_index ;

        echo "<br><h5> Starte DumpStatementRest()</h5>" . ' (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')' ;


        echo "<br> Unbearbeiteter Rest vom Statement ab Position {$pos} -><br>'" . substr( $this->m_statement, $this->m_char_index ) . "'";
        echo ' ( ' . debug_backtrace()[1]['function'] . ' )';

      }	// function DumpStatementRest( )

      
    /**
      *
      * The method StringFoundIn( ) returns true, if the string in the first parameter is found in the following parameters
      *
      * Example:
      *
      * @param string $cmp the string, which is compared with the following parameters
      * @return bool if $cmp is the same as one of the following parameters
      *
      */         
      
      
      static public function StringFoundIn( string $cmp ) : bool {

        // die Zeichenkette $cmp in den auf $cmp folgenden Paramtern suchen

        $numargs = func_num_args();

        $arg_list = func_get_args();

        for ( $i = 1; $i < $numargs; $i++ ) {

            if ( $arg_list[$i] == $cmp ) return true;

        }

        return false;

      }	// function StringFoundIn( );

    /**
      *
      * The method ScanTableSpecification( ) scans the actual query string and returns the next table specification which is in the buffer      
      * tbl_name [PARTITION (partition_names)] [[AS] alias] [index_hint_list]
      *
      * Example:
      *
      * @return string the table specification or an empty string
      *
      */   
      
    private function ScanTableSpecification( ) : string {

            

            // echo "<br> ScanTableSpecification( ) " . debug_backtrace()[1]['function'] . '/' .  debug_backtrace()[1]['line'];
            $specification = '';
            $factor = '';

            if ( $this->SkipSpaces( ) ) $specification .= ' ';

            $name = $this->ScanTableOrFieldName( ) ;
            $specification .= $name;

            if ( strtoupper( $name ) == 'FROM' ) {

                // echo "<br> ScanTableSpecification eliminiert einleitendes FROM";

                $name = $this->ScanTableOrFieldName( ) ;
                $specification = $name;

            }

            // echo "<br> ScanTableSpecification( ) startet mit '$name'";

            if ( $this->SkipSpaces( ) )  $specification .= ' ';

            $id_next = $this->NextIdentifier( );
            if ( ( $id_next == '' ) && ( $name == '' ) ) {
            }

            if ( strtoupper( $id_next ) == 'PARTITION' ) {
                $specification .= ' ' . $id_next;
                $this->SkipSpaces( );
                $this->ScanIdentifier( );

                if ( $this->SkipSpaces( ) ) $specification .= ' ';
                $specification .= $this->ScanPartitionlist( );

                if ( $this->SkipSpaces( ) ) $specification .= ' ';
                $id_next = $this->NextIdentifier( ) ;

            }

            // es folgt:
            //	ein Alias
            // 	ein AS und ein Alias
            //	USE, IGNORE oder FORCE
            //      ein JOIN-Statement
            //	ein Komma
            // ein ''

            //  echo "<br> ScanTableSpecification( ) ohne PARTITION - id_next = '$id_next' und specification = '$specification'";

            if ( ( $this->NextCh( ) == ',' ) || ( $this->NextCh( ) == '' ) ) {
                //  echo "<br> ScanTableSpecification( ) mit Komma liefert '$specification";
                return $specification;

            }

            $specification .= $this->ScanAlias( );


            if ( ( strtoupper( $id_next ) == 'USE' ) || ( $id_next == strtoupper( 'IGNORE' ) ) || ( strtoupper( $id_next ) == 'FORCE' ) ) {

                $this->SkipSpaces( );
                $this->ScanIdentifier( );

                $specification .= $id_next;
                if ( $this->SkipSpaces( ) ) $specification .= ' ';
                $specification .= $this->ScanIndexHintList( $id );

            } else {

        // 	      for ( $i = 0; $i < strlen( $id_next ); $i++ ) $this->UnGetCh( );

            }

            //  echo "<br> ScanTableSpecification() liefert ->$specification";

            return $specification;

            // war es wohl nicht, also war es der Alias -> diesen einfach ignorieren

            // nop()

      }	// function ScanTableSpecification( )
      
    /**
      *
      * The method DumpState( ) dumps the actual state of the scanner and prints $m_chr and the next identifier
      *
      * Example:
      *
      */         

      protected function DumpState( ) {

        echo "<br> m_chr = '$this->m_chr' und id_next = " . $this->NextIdentifier( );

      }	// function DumpState( );
      
    /**
      *
      * The method ScanTableFactor( ) scans the actual query string and returns the next table factor which is in the buffer      
      *
      * table_factor:
      * tbl_name [PARTITION (partition_names)] [[AS] alias] [index_hint_list]
      * | table_subquery [AS] alias
      *| ( table_references )      
      *
      * Example:
      *
      * @return string the table factor or an empty string
      *
      */         


      private function ScanTableFactor( ) : string {


	  // echo "<br> ScanTableFactor( )";

	  $factor = '';
	  $id = '';
	  $pos_start = $this->m_char_index;


/*
	  $token = $this->NextToken( );
	  if( strtoupper( $token == 'FROM' )  ) {
	      // nop();
	  } else {

	      for ( $i = 0; $i < 4; $i++ ) $this->UnGetCh( );

	  }
*/

	  if ( $this->SkipSpaces( ) ) $factor .= ' ';

	  if ( $this->m_chr == '(' ) {	// subquery or table references

	      $factor .= '(';

	      $this->GetCh( );

	      $factor .= $this->ScanSubQuery( );
	      // $factor .= ')';
	      // $this->GetCh( );
	      if ( $this->SkipSpaces( ) ) $factor .= ' ';

	      $factor .= $this->ScanAlias( );

/*
	      $id_next = strtoupper( $this->NextIdentifier( ) );
	      if ( 	( $id_next != 'AS'  ) &&
			( $id_next != '' ) &&
			( ! cMysql57_Utils::IsClauseStart( $id_next) ) &&
			( ! cMysql57_Utils::IsJoinStart( $id_next) )
			) {
		  // ein Alias?
		  $this->SkipSpaces( );
		  $identifier = $this->ScanTableOrFieldName( );
		  $factor .= $identifier;
	      }
*/
	      if ( \rstoetter\libsqlphp\cMysql57_Utils::IsJoinStart( $id_next) ) {

// 		  $this->RewindTo( $pos_start );

	      } else {


 		  $factor .= $this->ScanAlias( );
/*
		  $pos = $this->m_char_index;
		  $this->SkipSpaces( );
		  $id = $this->ScanIdentifier( true );
		  if ( strtoupper( $id ) == 'AS' ) {
		      $this->SkipSpaces( );
		      $id = $this->ScanIdentifier( );
		      $factor .= ' AS ';
		      $factor .= $id;
		  } else {
		      $this->RewindTo( $pos );
		      $id = '';
		  }
*/
	      }

	      if ( $this->SkipSpaces( ) ) $factor .= ' ';

	  } else {

		  // keine Subquery, also normale Gangart
	      $factor .= $this->ScanTableSpecification( );
	      if ( $this->SkipSpaces( ) ) $factor .= ' ';

	  }


	  $factor = trim( $factor );

	  //  echo "<br> ScanTableFactor( ) liefert ->$factor<-- an " . debug_backtrace()[1]['function'];;
	  return $factor;

      }	// function ScanTableFactor( )
      
    /**
      *
      * The method ScanAlias( ) scans the actual query string and returns the next alias definition which is in the buffer      
      *
      * Example:
      *
      * @return string the alias or an empty string
      *
      */         

      protected function ScanAlias( ) : string {

        $ret = '';

        $id_next = strtoupper( $this->NextToken( ) );

        if ( $id_next != '' ) {

            if ( $id_next == 'AS' ) {
            $ret .= ' AS ';
            $this->SkipSpaces( );
            $this->ScanIdentifier( );

            $this->SkipSpaces( );
            $ret .= $this->ScanIdentifier( );


            } elseif (
                ( ! \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( $id_next ) ) &&
                ( ! \rstoetter\libsqlphp\cMysql57_Utils::IsJoinStart( $id_next ) )  &&
                ( ! \rstoetter\libsqlphp\cMysql57_Utils::IsJoinSyntax( $id_next ) ) ){

            $ret .= ' AS ';

            $this->SkipSpaces( );
            $ret .= $this->ScanIdentifier( );

            }

        }

        return trim( $ret );

      }	// function ScanAlias( )


    /**
      *
      * The method ScanJoinTable( ) scans the actual query string and returns the next join table which is in the buffer      
      *
      * join_table:
      * table_reference [INNER | CROSS] JOIN table_factor [join_condition]
      *| table_reference STRAIGHT_JOIN table_factor
      * | table_reference STRAIGHT_JOIN table_factor ON conditional_expr
      * | table_reference {LEFT|RIGHT} [OUTER] JOIN table_reference join_condition
      * | table_reference NATURAL [{LEFT|RIGHT} [OUTER]] JOIN table_factor
      *
      * Example:
      *
      * @param bool $scanfortablename if true, then ScanJoinTable( ) tries first to read a table name. It defaults to true.
      *
      * @return string the join table or an empty string
      *
      */   


      private function ScanJoinTable( bool $scanfortablename = true ) : string {


        // Zustand: wenn Rekursionstiefe 1, dann erhält ScanJoinTable( ) den gesamten Join

            static $level = 0;	// die aktuelle Rekursionstiefe
            static $klammer_ebene = 0;	// wenn Klammerungen auftauchen
            $last_id = '';
            $name = '';
            $reference = '';
            $factor = '';
            $id = '';

            $level++;

            //  echo "<br> ScanJoinTable( ) - Aufruf von " . debug_backtrace()[1]['function'];

            $this->m_in_join = true;

            $pos_start = $this->m_char_index;
            $table = '';
            $join = '';


            if ( $this->SkipSpaces( ) ) $table .= ' ';
        //  	  $table .= $this->ScanTableFactor( );


            if ( $scanfortablename) {
                $table .= $this->ScanTableReference( false );
                if ( $this->SkipSpaces( ) ) $table .= ' ';
            } else {
                $this->SkipSpaces( );
            }

            // der aktuelle Join ist noch nicht eingetragen, also verwenden wir count ohne -1
            $this->m_a_join_tables[]= array( count( $this->m_a_joins ),  $table );

            $last_id = $id;
            $id = $this->ScanIdentifier( true );
            if ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( $id ) ) {

                $fertig = true;
                for ( $i = 0; $i < strlen( $id ); $i++ ) { $this->UnGetCh( ); }

            } else {
                $this->SkipSpaces( );
                $fertig = false;
            }

            while ( ! $fertig ) {

                $id = trim( $id );

                if ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( $id ) ) ) {
                $fertig = true;
                }


                if ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( $id ) ) {
                for ( $i = 0; $i < strlen( $id ); $i ++ ) $this->UnGetCh( );
                $fertig = true;

                } elseif ( $this->m_chr == '' ) {

                $fertig = true;

                } elseif ( $this->m_chr == '(' ) {

                $klammer_ebene++;
                $this->GetCh( );		// skip '('
                $this->SkipSpaces( );

                } elseif ( $this->m_chr == ')' ) {

                $klammer_ebene--;
                $this->GetCh( );		// skip ')'
                $this->SkipSpaces( );

                if ( $id == '' ) {

                    $last_id = $id;
                    $this->SkipSpaces( );
                    $id = $this->ScanIdentifier( true );	// skip 'JOIN'

                }

                echo "<br> id = '$id' und ')' gefunden ";

                } elseif ( ( $id == 'ON' ) || ( $id == 'USING' ) ) {



                    $this->SkipSpaces( );
                    if ( $id == 'USING' ) {
                    $this->ScanColumnList( );
                    } else {
                    // $this->ScanJoinCondition( $id );

                    $expr = $this->ScanConditionalExpression( );

                    }

                } elseif ( ( $id == 'INNER' ) || ( $id == 'CROSS' ) ) {

                $join .= $id . ' ';

                $last_id = $id;
                $this->SkipSpaces( );
                $id = $this->ScanIdentifier( true );	// skip 'JOIN'
                if ( $this->SkipSpaces( ) ) $join .= ' ';

                $join .= $id . ' ';

                $factor = $this->ScanTableFactor( );

                // der aktuelle Join ist noch nicht eingetragen, also verwenden wir count ohne -1
                $this->m_a_join_tables[]= array( count( $this->m_a_joins ),  $factor );

                $this->SkipSpaces( );

                $join .= $factor . ' ';
                if ( $this->SkipSpaces( ) ) $join .= ' ';

                $last_id = $id;
                $id = $this->ScanIdentifier( true );
                if ( $id == 'USING' || ( $id == 'ON' ) ) {

                    if ( $id == 'USING' ) {
                    $this->ScanColumnList( );
                    } else {
                    // $this->ScanJoinCondition( $id );
                    $this->ScanConditionalExpression( );
                    }

                } else {



                }


                } elseif ( $id == 'JOIN' ) {

                    $last_id = $id;
                    $this->SkipSpaces( );
                    $id = $this->ScanIdentifier( true );

                    if ( $id == 'USING' || ( $id == 'ON' ) ) {

                        if ( $id == 'USING' ) {

                        $this->ScanColumnList( );

                        } else {

                        $this->ScanConditionalExpression( );

                        }

                    } else {

                        for ( $i = 0; $i < strlen( $id ); $i ++ ) $this->UnGetCh( );

                        $factor = $this->ScanTableFactor( );

                        // der aktuelle Join ist noch nicht eingetragen, also verwenden wir count ohne -1
                        $this->m_a_join_tables[]= array( count( $this->m_a_joins ),  $factor );

                        $this->SkipSpaces( );

                        $this->SkipSpaces( );
                        $last_id = $id;	// ???
                        $token = $this->ScanIdentifier( true );
                        if ( $token == 'ON' ){
                        $this->ScanConditionalExpression( );
                        }


                    }

                } elseif ( $id == 'STRAIGHT_JOIN' ) {


                $last_id = $id;
                $this->SkipSpaces( );
        // 		  $id = $this->ScanIdentifier( true );

                $factor = $this->ScanTableFactor( );
        // 		  $this->m_a_join_tables[]= $factor;

                // der aktuelle Join ist noch nicht eingetragen, also verwenden wir count ohne -1
                $this->m_a_join_tables[]= array( count( $this->m_a_joins ),  $factor );



                $this->SkipSpaces( );
                $id = $this->ScanIdentifier( true );

                } elseif ( ( $id == 'LEFT' ) || ( $id == 'RIGHT' ) ) {

                    $last_id = $id;
                    $this->SkipSpaces( );
                    $id = $this->ScanIdentifier( true );
                    $this->SkipSpaces( );

                    if ( $id == 'OUTER' ) {

                        $last_id = $id;
                        $this->SkipSpaces( );
                        $id = $this->ScanIdentifier( true );
                        $this->SkipSpaces( );

                    }

                    assert( $id == 'JOIN' );
                    $this->SkipSpaces( );

                    if ( $this->m_chr == '(') {$klammer_ebene++; $this->GetCh( ); }

                    $this->SkipSpaces( );
                    $reference = $this->ScanTableReference( );

                    // der aktuelle Join ist noch nicht eingetragen, also verwenden wir count ohne -1
                    $this->m_a_join_tables[]= array( count( $this->m_a_joins ),  $reference );


                    $last_id = $reference;
                    $this->SkipSpaces( );
                    $id = $this->ScanIdentifier( );

                    if ( ( ! \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( $id ) ) ) && ( ! \rstoetter\libsqlphp\cMysql57_Utils::IsJoinStart( strtoupper( $id ) ) ) ) {
                        $this->SkipSpaces( );
                        $condition = $this->ScanJoinCondition( $id );

                        $last_id = $id;
                        $this->SkipSpaces( );
                        $id = $this->ScanIdentifier( );

                    } else {

                    }

                } elseif ( $id == 'NATURAL' )  {

                    $this->SkipSpaces( );

                    $last_id = $id;
                    $this->SkipSpaces( );
                    $id = $this->ScanIdentifier( true );	// skip 'NATURAL'

                    if ( $id == 'LEFT' || $id == 'RIGHT'  ) {

                        $last_id = $id;
                        $this->SkipSpaces( );
                        $id = $this->ScanIdentifier( true );

                    }

                    if ( $id == 'OUTER' ) {

                        $last_id = $id;
                        $this->SkipSpaces( );
                        $id = $this->ScanIdentifier( true );

                    }

                    assert( $id == 'JOIN' );

                    $this->SkipSpaces( );
                    $factor = $this->ScanTableFactor( );
            // 		  $this->m_a_join_tables[]= $factor;

                    // der aktuelle Join ist noch nicht eingetragen, also verwenden wir count ohne -1
                    $this->m_a_join_tables[]= array( count( $this->m_a_joins ),  $factor );


                    $this->SkipSpaces( );
                    $id = $this->ScanIdentifier( );

                } elseif ( ( $id == 'USE' ) || ( $id == 'IGNORE' ) || ( $id == 'FORCE' ) ) {

                    $last_id = $id;
                    $id = $this->ScanIndexHintList( $id );

                } elseif ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( $id ) ) ) {

                    $fertig = true;
                    for ( $i = 0; $i < strlen( $id ); $i++ ) { $this->UnGetCh( ); }

                } else {

                $this->SkipSpaces( );

                if ( ( ( $klammer_ebene == 0 ) && ( $id == '') ) &&
                    ! \rstoetter\libsqlphp\cMysql57_Utils::IsJoinStart( strtoupper( $this->NextToken() )) )
                    $fertig = true;


                }

                if ( $id == '' ) {

                $this->SkipSpaces( );
                $id = $this->ScanIdentifier( );
                }

                    if ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( $this->NextToken( ) ) ) {
                    $fertig = true;
                        echo "<br> clause start gefunden, gehe zurück zur aufrufenden Funktion " . debug_backtrace()[1]['function'];;

                    }

            }	// of while

            $ret = substr( $this->m_statement, $pos_start, $this->m_char_index - $pos_start );

            if ($this->m_debug_engine >= 0 ) echo "<br> ScanJoinTable( ) liefert '$ret' an " . debug_backtrace()[1]['function'];


            $level--;
            if ( $level == 0 ) $this->m_in_join = false;

            return $ret;

      }	// function ScanJoinTable( )

      
    /**
      *
      * The method FollowsJoin( ) scans the actual query string and returns true, if a join follows in a table reference      
      *  table_reference:
      *      table_factor
      *  | join_table
      *
      *  table_factor:
      *      tbl_name [PARTITION (partition_names)]
      *          [[AS] alias] [index_hint_list]
      *  | table_subquery [AS] alias
      *  | ( table_references )
      *
      * join_table:
      *      table_reference [INNER | CROSS] JOIN table_factor [join_condition]
      *  | table_reference STRAIGHT_JOIN table_factor
      *  | table_reference STRAIGHT_JOIN table_factor ON conditional_expr
      *  | table_reference {LEFT|RIGHT} [OUTER] JOIN table_reference join_condition
      *  | table_reference NATURAL [{LEFT|RIGHT} [OUTER]] JOIN table_factor
      *
      *
      *
      * Example:
      *
      * @return bool true, if a join follows
      *
      */         
      
      private function FollowsJoin( ) : bool {


        // Was folgt nun in der Tabellenreferenz?

        $reference = '';
        $position = 0;

        $position = $this->m_char_index;

        $reference .= $this->ScanTableFactor( );

        if ( ( $reference == '' ) && ( $this->m_chr != '' ) ) {
            echo "<br> FollowsJoin( ) Join detected!";
            return true;
        }


        $this->SkipSpaces( );
        $name = $this->ScanTableOrFieldName( );

        $this->RewindTo( $position );

        $ret = \rstoetter\libsqlphp\cMysql57_Utils::IsJoinStart( strtoupper( $name ) ) || ( strtoupper( $name ) == 'ON'  );
        //  echo "<br> FollowsJoin( ) liefert " . ( $ret ? 'true' : 'false' ) ;

        return $ret;

      }	// function FollowsJoin( )
      
    /**
      *
      * The method NextToken( ) scans the actual query string and returns the next token without moving the internal pointer to the text buffer
      *
      * Example:
      *
      * @return string the next token or an empty string
      *
      */         
      

      private function NextToken( ) : string {

        $pos = $this->m_char_index;

        $this->SkipSpaces( );
        $ret = $this->ScanTableOrFieldName( );

        $this->RewindTo( $pos );

        return $ret;

      }	// function NextToken( )
      
      
    /**
      *
      * The method ScanTableReference( ) scans the actual query string and returns the next table reference which is in the buffer      
      *
      * table_reference:
	  *    table_factor
	  *  | join_table      
      *
      *
      * Example:
      *
      * @param bool $check_join if true, then ScanTableReference( ) tries to resolve joins, too. It defaults to true.
      *
      * @return string the table reference or an empty string
      *
      */   

      
      private function ScanTableReference( bool $check_join = true ) : string {



	  //  echo "<br> ScanTableReference( ) startet mit \"" . $this->m_chr . '" (' . debug_backtrace()[1]['function'] . ' /' . debug_backtrace()[0]['line'] . ')';

	  $follows_join = false;
	  $reference = '';
	  $xpos2 = $xpos = $this->m_char_index;
	  $name = '';


	 if ( !$this->m_in_join ) $follows_join = $this->FollowsJoin( );

	  if ( $this->SkipSpaces( ) ) $reference .= ' ';

	  $fertig = false;
	  while( ! $fertig ) {

	      if ( $this->m_chr == ';' ) {

		  $fertig = true;

	      } elseif ( $this->m_chr != ',' ) {

		  if ( $follows_join ) {
		      //  echo "<br> ScanTableReference( ) erwarte JOIN";
		      $xpos = $this->m_char_index;
		      $join = $this->ScanJoinTable( );
		      $this->m_a_joins[]= $join;
		      $reference .= $join;


		  } elseif ( $this->m_chr == '' ) {

		      echo $references;
		      $fertig = true;

		  }  else {

// 		    $this->RewindTo( $pos_start  );


		    // echo "<br> ScanTableReference erwarte Table Factor";

		    $xpos = $this->m_char_index;
		    $factor = $this->ScanTableFactor( );	// da steht jetzt alles drin in $name
			$fertig = true;
			$reference .= $factor;

		    if ( ( ! $this->m_in_join) && ( \rstoetter\libsqlphp\cMysql57_Utils::IsJoinStart( strtoupper( $this->NextToken() ) ) ) ) {

			$name .= $this->ScanJoinTable( false );

		    }
		    if ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( $name ) ) ) {
			$fertig = true;

// 			for ( $i = 0; $i < strlen( $name ); $i++ ) { $this->UnGetCh( ); }
// 			$name = '';
			$this->RewindTo( $xpos );

		    }



		    if ( ! $fertig ) $reference .= $name;

		    if ( ! $fertig ) {
			if ( ! strlen( $name ) ){

			    $this->DumpStatementAbgearbeitet( );
			    die("<br> Abbruch: ScanTableReference() bekommt leere Zeichenkette von ScanTableFactor() - reference = '$reference'");
			}
		    }

		  }

		  if ( ! $fertig ) if ( $this->SkipSpaces( ) ) $reference .= ' ';

	      } else {

		  // echo "<br> übernehme Name '$name'";

		  if ( $this->m_chr == ',' ) {

		      $fertig = true;

		      // $reference .
		      // $this->GetCh( );

		      // $pos_start = $this->m_char_index;

		      // $name = $this->ScanTableOrFieldName( );
		      // if ( $this->SkipSpaces( ) ) $gemerkt .= ' ';

		  } else {
		    die ( "Unknown disposition - chr = '$this->m_chr'" );
		  }

	      }


	      if ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( $this->NextToken( ) ) ) {

		  $fertig = true;

// 			for ( $i = 0; $i < strlen( $name ); $i++ ) { $this->UnGetCh( ); }
// 			$name = '';
//  			$this->RewindTo( $xpos );

	      }



	      if ( $this->m_chr == '' || $this->m_chr == ';' ) {

		  $fertig = true;

	      }

	      $follows_join = false;

	  }	// while

	  //  echo "<br> ScanTableReference liefert ->$reference";

	  return trim( $reference );

      }	// function ScanTableReference( )


    /**
      *
      * The method ScanEscapedTableReference( ) scans the actual query string and returns the next escaped table reference which is in the buffer      
      *
      * escaped_table_reference:
      *     table_reference
      *   | { OJ table_reference }
      *
      *
      * Example:
      *
      * @return string the escaped table reference or an empty string
      *
      */   
      
      
      
      private function ScanEscapedTableReference( ) : string {


 	    //  echo "<br> ScanEscapedTableReference( )";

	    $reference = '';

	    if ( $this->SkipSpaces( ) ) $reference .= ' ';

	    $fertig = false;

	    if ( $this->SkipSpaces( ) ) $reference .= ' ';

	    if ( ( $this->m_chr == '' ) || ( $this->m_chr == ';' ) ) {

		 echo '<br>' . substr( $this->m_statement,  $this->m_char_index - 20 , 70);
		 die( "<br>ScanEscapedTableReference: string ends inmidst the escaped table reference" );

	    }  elseif ( $this->m_chr == '{' ) {		// new version: analyzer

		$reference .= ' {';			// ODBC-Join
		$this->GetCh( );			// skip '{'
		$this->m_in_curly_braces = true;
		$this->SkipSpaces( );
		$id = $this->ScanIdentifier( );
		assert( strtoupper( $id ) == 'OJ' );
		$reference .= ' OJ ';
		$reference .= $this->ScanTableReference( );

		$this->SkipSpaces( );



// 		$reference .= '}';
// 		$this->GetCh( );
		if ( $this->SkipSpaces( ) ) $reference .= ' ';
		assert( $this->m_chr == '}'  );
		$this->GetCh( );
		$reference .= '}';
		$this->m_in_curly_braces = false;
		$this->m_a_joins[ count( $this->m_a_joins ) - 1  ] = $reference;

	    } elseif ( $this->m_chr == '{' ) {	// old version: scan until ending }`

		$reference .= ' {';			// ODBC-Join TODO
		$this->GetCh( );			// skip '{'
		$this->m_in_curly_braces = true;
		$reference .= $this->ScanUntilFolgezeichen('}');
		$this->m_in_curly_braces = false;
		$this->m_a_joins[] = $reference;
// 		$reference .= '}';
// 		$this->GetCh( );
		if ( $this->SkipSpaces( ) ) $reference .= ' ';



	    } elseif ( $this->m_chr == ',' ) {

		  $fertig = true;
		  $reference .= ',';

	    } else {

		if ( !( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( $this->NextToken( ) ) ) ) ) {

		    $ref = $this->ScanTableReference( );
		    $reference .= $ref;
		} else {
		}

	    }

	    //  echo "<br> ScanEscapedTableReference liefert ->$reference";

	    return $reference;

      }	// function ScanEscapedTableReference( )
      
      
    /**
      *
      * The method FollowsSubquery( ) scans the actual query string and returns true, if there follows a subquery in the buffer      
      *
      *
      * Example:
      *
      * @return bool true, if there follows a subquery
      *
      */   
      

      protected function FollowsSubquery( ) : bool {

	  $ret = false;
	  $pos = $this->m_char_index;

	  $this->SkipSpaces( );

	  if ( $this->m_chr == '(' ) {

	      $this->GetCh( );
	      $this->SkipSpaces( );
	      $ret = ( strtoupper( $this->NextToken( ) ) == 'SELECT' );

	  }

	  $this->RewindTo( $pos );

	  return $ret;


      }	// function FollowsSubquery( )
      
    /**
      *
      * The method ScanTableReferences( ) scans the actual query string and returns the next table references which is in the buffer      
      *
	  * table_references:
	  * escaped_table_reference [, escaped_table_reference]
      *
      *
      * Example:
      *
      * @return string the table references or an empty string
      *
      */         


	private function ScanTableReferences( ) : string {


	    //  echo "<br> ScanTableReferences( ) :scanne nun die Tabellenreferenzen zwischen FROM und WHERE oder anderem Clause";

	    $i = 0;
	    $references = '';
	    $klammern = 0;
	    $reference = '';

	    if ( $this->SkipSpaces( ) ) $references .= ' ';

	    if ( ( $this->m_chr == '(') && ( ! $this->FollowsSubquery( ) ) ) {
            $this->GetCh( );

            $this->SkipSpaces( );
            if ( $this->NextCh( ) == ')' ) {

                $this->GetCh( );
                $this->ScanTableReferences( );

            } else {
    // 		    die("<br> Abbruch : ScanTableReference() : ')' erwartet ". __LINE__);
            }


	    } else {
            $reference = $this->ScanEscapedTableReference( );

	    }

	    $references .= $reference;
	    $this->m_a_tables[]= $reference;

	    if ( $this->SkipSpaces( ) ) $references .= ' ';

	    $fertig = false;

	    if ( $this->m_chr == ',') {
            while( !$fertig ) {

                $pos = $this->m_char_index;

                if ( $this->SkipSpaces( ) ) $references .= ' ';

                if ( ( $this->m_chr == '' ) ||  ( $this->m_chr == ';' ) ) {
                $fertig = true;
    // 			 die( "sql string ends inmidst the field list" );
                } elseif ( $this->m_chr == '(' ) {

                $klammern++;
                $this->GetCh( );
                } elseif ( $this->m_chr == ')' ) {
                $klammern--;
                $this->GetCh( );


                } elseif ( $this->m_chr == ',' ) {

                $references .= ' , ';
                $this->GetCh( );
                if ( $this->SkipSpaces( ) ) $references .= ' ';
                $references_new = $this->ScanEscapedTableReference( );
                assert( $references != $references_new );
                $references .= $references_new;
                $this->m_a_tables[]= $references_new;

                } else {

                $fertig = true;
                $this->RewindTo( $pos );

                }

            }	// while

	    }

	    //  echo "<br> ScanTableReferences( ) liefert-> $references";

	    return $references;

	}	// function ScanTableReferences( )

    /**
      *
      * The method ScanWhereCondition( ) scans the actual query string and returns the next WHERE clause which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @return string the WHERE clause or an empty string
      *
      */   
      
	private function ScanWhereCondition( ) : string {

	    $clause = '';

	    //  echo "<br> ScanWhereCondition( )";

	    $this->m_a_where = array( );	// die WHERE-Felder
	    $this->m_in_where = true;

	    $clause = $this->ScanConditionalExpression( );

	    $this->m_in_where = false;

	    //  echo "<br> ScanWhereCondition( ) liefert $clause";

	    return $clause;

	}	// function ScanWhereCondition( )
	
    /**
      *
      * The method ScanHavingCondition( ) scans the actual query string and returns the next HAVING clause which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @return string the HAVING clause or an empty string
      *
      */   	
	

	private function ScanHavingCondition( ) : string {

	    $clause = '';

	    //  echo "<br> ScanHavingCondition( )";

	    $this->m_in_having = true;

	    $clause = $this->ScanConditionalExpression( );

	    $this->m_in_having = false;

	    //  echo "<br> ScanHavingCondition( ) liefert $clause";

	    return $clause;

	}	// function ScanHavingCondition( )
	
    /**
      *
      * The method ScanGroupByCondition( ) scans the actual query string and returns the next GROUP BY clause which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @param array $ary the items of the GROUP BY clause
      *
      * @return string the GROUP BY clause or an empty string
      *
      */   	

	private function ScanGroupByCondition( array & $ary ) : string {

	    //  echo "<br> ScanGroupByCondition( )";

	    $this->m_in_group_by = true;

	    $this->SkipSpaces( );

	    $ary = array( );

	    $list = $this->ScanFieldList( $ary );

	    $this->m_in_group_by = false;

	    //  echo "<br> ScanGroupByCondition liefert->$list";

	    return $list;


	}	// function ScanGroupByCondition( )
	
    /**
      *
      * The method ScanOrderByCondition( ) scans the actual query string and returns the next ORDER BY clause which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @param array $ary the items of the ORDER BY clause
      *
      * @return string the ORDER BY clause or an empty string
      *
      */   		

	private function ScanOrderByCondition( array & $ary ) : string {

	    //  echo "<br> scanne nun nach order by";

	    $ary = array( );

	    $pos = $this->m_char_index;
	    $startpos = $this->m_char_index;

//	    $this->m_field_start = $pos;

	    $this->SkipSpaces( );

	    $fertig = false;
	    $fieldlist = '';
	    $act = '';

 	    $identifier = $this->ScanTableOrFieldName( );

 	    if ( strtoupper( $identifier ) == 'ORDER' ) {
		$this->RewindTo( $pos );
		return '';
 	    }

	    // echo '<br> ScanOrderByCondition: starte Scan mit id = ' . $identifier;

   	    $this->SkipSpaces( );

	    while( !$fertig ) {

		// echo "<br> ScanOrderByCondition() mit  identifier = $identifier und NextIdentifier = " . $this->NextIdentifier();
		$next_identifier = $this->NextIdentifier( );

// echo "<br> next identifier is $next_identifier";

		  if ( ( strtoupper( trim( $identifier ) ) == 'FROM' ) ||
		      \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( trim( $identifier ) ) ) ) {
		      // IsClauseStart() hinzugefügt, damit auch andere Methoden diese Methode verwenden können

		    $fertig = true;

		     // echo "<br> ScanOrderByCondition() ->RewindTo( $pos ) ";
 		    $this->RewindTo( $pos );

/*
		    for ( $i = 0; $i < strlen( $identifier ); $i++ ) {
			$this->UnGetCh( );
		    }
*/
		} elseif ( ( strtoupper( trim( $next_identifier ) ) == 'FROM' ) ||
		      \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( trim( $next_identifier ) ) ) ) {
		      // IsClauseStart() hinzugefügt, damit auch andere Methoden diese Methode verwenden können
// echo "<br> cleaning up identifier with $identifier";
		    if ( strlen( $identifier ) ) {
			$fieldlist .= $identifier;
			$ary[] = trim( $identifier );
			$this->m_a_columns[] = trim( $identifier );
		    }
// echo "<br> field list is now '$fieldlist'";
		    $fertig = true;
/*
		    for ( $i = 0; $i < strlen( $next_identifier ); $i++ ) {
			$this->UnGetCh( );
		    }
*/

		} elseif ( strlen( $identifier ) > 0 ) {


		    if ( strtoupper( $identifier ) == 'CASE' ) {
			$this->RewindTo( $startpos );

		    } else {

// 		    $fieldlist .= $identifier;
			$act .= ' ' . $identifier;
		    }

		    $pos = $this->m_char_index;
//  		    $identifier = '';

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		    if  ( ( strtoupper( $identifier == 'ASC' ) ) || ( strtoupper( $identifier == 'DESC' ) ) ) {

			  // echo "<br> ASC oder DESC : $identifier";

   			  $identifier = $this->ScanIdentifier( );

			  $pos = $this->m_char_index;

			  $this->m_a_field_aliases[]= $identifier;

			  $fieldlist .=  ' ' . $identifier;
			  $act .=  $identifier;
			  $identifier = '';


			  if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }
 			  $identifier = $this->ScanIdentifier( );

		    } else  {
			// echo "<br> skipping identifier '$identifier'";

			$identifier = '';

			$fieldlist .= $identifier;

			$identifier = $this->ScanTableOrFieldName( );

		      }


		}  elseif ( $this->FollowsOperator( ) ) {

		    $pos = $this->m_char_index;

		    $operator = $this->ScanOperator( );
		    $operator = ' ' . $operator . ' ';

		    // echo "<br> ScanOrderByCondition( ) findet Operator '$operator'";

		    $fieldlist .= $operator;
		    $act .=  $operator;

		    // echo "<br> fieldlist = '$fieldlist'";

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( $this->IsNumberStart( ) ) {

		    $pos = $this->m_char_index;
		    $number = $this->ScanNumber( );
		    $fieldlist .= $number;
		    $act .=  $number;

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( ( $this->m_chr == '' ) || ( $this->m_chr == ';' ) ) {

		    // echo "<br> fieldlist = $fieldlist";
		    // echo "<br> act = $act";

		    $fertig = true;	// Abschluss, da Ende der Abfrage erreicht

// 		    die( "<br> error: sql string ends inmidst the field list" );

		} elseif ( $this->m_chr == ',' ) {

		    if ( strlen( trim( $act ) )  ) {
			$ary[] = trim( $act );
			$this->m_a_columns[]= trim( $act );
			$fieldlist .= trim( $act );

		    }
;
		    $fieldlist .=  ' , ';

		    $act = '';
		    $this->GetCh( );
		    if ( $this-> SkipSpaces( ) ) $fieldlist .= ' ';

		} elseif ( $this->m_chr == '.' ) {

		    $fieldlist .= '.';
		    $act .= '.';

		    $this->GetCh( );
		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( strpos( '*/-+!=', $this->m_chr ) !== false ) {

		    $fieldlist .= $this->m_chr;
		    $act .= $this->m_chr;

		    $this->GetCh( );
		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }


		} elseif ( $this->m_chr == '"' ) {

		    $fieldlist .= '"';
		    $act .= '"';
		    $this->GetCh( );

		    $str = $this->ScanUntilFolgezeichen( '"' );
		    $fieldlist .= $str;
		    $act .= $str;

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( $this->m_chr == '`' ) {

		    $fieldlist .= '`';
		    $act .= '`';
		    $this->GetCh( );

		    $str = $this->ScanUntilFolgezeichen( '`' );
		    $fieldlist .= $str;
		    $act .= $str;

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		}  elseif ( $this->m_chr == "'" ) {

		    $fieldlist .= "'";
		    $act .= "'";
		    $this->GetCh( );

		    $str = $this->ScanUntilFolgezeichen( "'" );
		    $fieldlist .= $str;
		    $act .= $str;

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( $this->m_chr == '(' ) {

		    $fieldlist .= '(';
		    $act .= '(';
		    $this->GetCh( );

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		    $statement = $this->ScanSubQuery( );

		    $fieldlist .= $statement;
		    $act .= $statement;

		    $this->SkipSpaces( );

		}  elseif ( $this->is_ctype_identifier_start( $this->m_chr ) ) {

		    $pos = $this->m_char_index;
		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

 		    $identifier = $this->ScanTableOrFieldName( );
 		    $this->SkipSpaces( );
		} else {
		    $fieldlist .= $identifier;
		    $act .= $identifier;
		    $identifier = $this->ScanTableOrFieldName( );

		}

	    }	// while !fertig

 	    if ( strlen( $act ) ) {

		$ary[] = trim( $act );
 		$this->m_a_columns[]= trim( $act );
	    }
/*
	    if ( ( strlen( $identifier ) ) && ( strtolower( $identifier ) != 'from' ) ) {
 	    // if ( strlen( $identifier ) ) {

		$ary[] = trim( $identifier );
 		$this->m_a_columns[] = trim( $identifier );
 		$fieldlist .= ( strlen( $fieldlist) ? ',' : '') . trim( $identifier );
 		$pos = $this->m_char_index;
	    }
*/
 	    $act = '';

//	    $this->m_field_end = $pos;


	  if ( false ) {
	      echo "\n<br> leaving ScanOrderByCondition() with ";
	      echo "\n<br> ary       ="; print_r ($ary);
	      echo "\n<br> fieldlist ="; print_r ($fieldlist);
	      echo "\n<br> a_columns ="; print_r( $this->m_a_columns );
	  }

	    return $fieldlist;

	}	// function ScanOrderByCondition( )
	
	
    /**
      *
      * The method NextIdentifier( ) scans the actual query string and returns the next identifier clause which is in the buffer without moving the internal pointer to the text buffer     
      *
      *
      *
      * Example:
      *
      * @return string the following identifier or an empty string
      *
      */   		


	private function NextIdentifier( ) : string {

	    $ret = false;
	    $pos = $this->m_char_index;

	    $this->SkipSpaces( );
	    // $ret = ( strlen( $this->ScanTableOrFieldName( ) ) > 0 );
	    $ret = $this->ScanTableOrFieldName( );

	    $this->RewindTo( $pos );

	    return $ret;


	}	// function NextIdentifier( )

    /**
      *
      * The method ScanFieldList( ) scans the actual query string and returns the next field list which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @param array $ary the items of the field list in a string array
      *
      * @return string the field list or an empty string
      *
      */   		


	private function ScanFieldList( array & $ary ) : string {

	    //  echo "<br> scanne nun nach der Feldliste";

	    // liefert die Datenbankfelder in $ary als Array und $fieldlist als Zeichenkette

	    // echo "<br> " . debug_backtrace()[1]['function'] . ' line=' . debug_backtrace()[0]['line'] . " scannt nach Feldliste in ScanFieldList( )";

	    $ary = array( );

	    $this->m_a_columns = array();

	    $pos = $this->m_char_index;
	    $startpos = $this->m_char_index;

	    // echo "<br>ScanFieldList() mit SQL = "; echo $this->GetStatementHTML( );
	    // echo "<br>noch abzuarbeiten = "; echo $this->DumpStatementRest( );

//	    $this->m_field_start = $pos;

	    $this->SkipSpaces( );

	    $fertig = false;
	    $fieldlist = '';
	    $act = '';

 	    $identifier = $this->ScanTableOrFieldName( );

 	    if ( strtoupper( $identifier ) == 'FROM' ) {
		$this->RewindTo( $pos );
		return '';
 	    }

	    // echo '<br> ScanFieldList: starte Scan mit id = ' . $identifier;

   	    $this->SkipSpaces( );

	    while( !$fertig ) {

		// echo "<br> ScanFieldList() mit  identifier = $identifier und NextIdentifier = " . $this->NextIdentifier();
		$next_identifier = $this->NextIdentifier( );

		  if ( ( strtoupper( trim( $identifier ) ) == 'FROM' ) ||
		      \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( trim( $identifier ) ) ) ) {
		      // IsClauseStart() hinzugefügt, damit auch andere Methoden diese Methode verwenden können

		    $fertig = true;

 		    $this->RewindTo( $pos );
/*
		    for ( $i = 0; $i < strlen( $identifier ); $i++ ) {
			$this->UnGetCh( );
		    }
*/
		} elseif ( ( strtoupper( trim( $next_identifier ) ) == 'FROM' ) ||
		      \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( trim( $next_identifier ) ) ) ) {
		      // IsClauseStart() hinzugefügt, damit auch andere Methoden diese Methode verwenden können
// echo "<br> cleaning up identifier with $identifier";

		   if ( strlen( trim( $identifier ) ) ) {
			$fieldlist .= $identifier;
			$ary[] = trim( $identifier );
			// if ( $identifier == 'LAND' || $identifier == 'ANZAHL') die("<br>Abbruch mit Identifier = $identifier");
			$this->m_a_columns[] = trim( $identifier );
		    }
// echo "<br> field list is now '$fieldlist'";
		    $fertig = true;
/*
		    for ( $i = 0; $i < strlen( $next_identifier ); $i++ ) {
			$this->UnGetCh( );
		    }
*/

		} elseif ( strlen( $identifier ) > 0 ) {


		    if ( strtoupper( $identifier ) == 'CASE' ) {
			$this->RewindTo( $startpos );

		    } else {

// 		    $fieldlist .= $identifier;
			$act .= ' ' . $identifier;
		    }

		    $pos = $this->m_char_index;
//  		    $identifier = '';

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }
		    if  ( strtoupper( $identifier == 'AS' ) ) {
// 			  ( strtoupper( $this->NextIdentifier( ) == 'AS' ) ) {
			  // es muss ein Alias mit AS folgen

 			  $identifier = $this->ScanIdentifier( );	// skip 'AS'
$pos = $this->m_char_index;
			  if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }
//  			  $identifier = $this->ScanIdentifier( );	// get alias

			  // TODO diese Abfrage mit in_array sollte nicht nötig sein
 			  if ( ! in_array( $identifier, $this->m_a_field_aliases ) ) {
			      $this->m_a_field_aliases[]= $identifier;

			      $fieldlist .= ' ' .  $identifier;
			      $act .=  ' ' . $identifier;

			  }

			  $identifier = '';

			  if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		      } elseif ( ( strlen( $identifier ) ) &&
 			  ( ! $this->StringFoundIn( strtoupper( $this->NextIdentifier( ) ), 'FROM', 'AS', ''  ) ) ) {
			  // es muss ein Alias ohne AS folgen
   			  $identifier = $this->ScanIdentifier( );
$pos = $this->m_char_index;
			  $this->m_a_field_aliases[]= $identifier;
			  // echo "<br> addiere übriggebliebenen identifier - Alias! - $identifier";

			  $fieldlist .=  ' ' . $identifier;
			  $act .=  ' ' . $identifier;
			  $identifier = '';


			  if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }
 			  $identifier = $this->ScanIdentifier( );

		      }  else {
// echo "<br> skipping identifier '$identifier'";
	      $identifier = '';


			  $fieldlist .= $identifier;
// 			  $act .= $identifier;
// 		$this->m_a_columns[]= trim( $act );
// 		$act = '';
// echo '<br> act wird zu ' . $act;
		 $this->SkipSpaces( );
  		 $identifier = $this->ScanTableOrFieldName( );

//  	      $identifier = '';

		      }


		}  elseif ( $this->m_chr == '*' ) {

		    $fieldlist .= '*';
		    $ary[] = '*';
		    $this->m_a_columns[]= '*';

//		    $act .= '*';
$act = '';

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }
		    $this->GetCh( );
		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }


		} elseif ( $this->FollowsOperator( ) ) {

		    $pos = $this->m_char_index;

		    $operator = $this->ScanOperator( );
		    $operator = ' ' . $operator . ' ';

		    // echo "<br> ScanFieldList( ) findet Operator '$operator'";

		    $fieldlist .= $operator;
		    $act .=  $operator;

		    // echo "<br> fieldlist = '$fieldlist'";

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( $this->IsNumberStart( ) ) {

		    $pos = $this->m_char_index;
		    $number = $this->ScanNumber( );
		    $fieldlist .= $number;
		    $act .=  $number;

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( ( $this->m_chr == '' ) || ( $this->m_chr == ';' ) ) {

		    // echo "<br> fieldlist = $fieldlist";
		    // echo "<br> act = $act";

		    $fertig = true;	// Abschluss, da Ende der Abfrage erreicht

// 		    die( "<br> error: sql string ends inmidst the field list" );

		} elseif ( $this->m_chr == ',' ) {

		if ( false ){
		     if ( trim( $act ) == 'LAND' || trim( $act ) == 'ANZAHL' ) {
			echo $this->GetStatementHTML( );
			echo $this->DumpStatementRest( );
			die( "<br>Abbruch: act = $act" );

		    }
		}


		    if ( strlen( trim( $act ) )  ) {
			$ary[] = trim( $act );
			$this->m_a_columns[]= trim( $act );
			$fieldlist .= trim( $act );

		    }

;
		    $fieldlist .=  ' , ';
		    // echo "<br>act = $act";
		    $act = '';
		    $ch = $this->GetCh( );

			// echo( "<br>Komma erhalten " );
			// echo $this->DumpStatementRest( );
			// var_dump( $ary );
			// die( "<br>Abbruch: kein Komma" );


		    if ( $this-> SkipSpaces( ) ) $fieldlist .= ' ';

		} elseif ( $this->m_chr == '.' ) {

		    $fieldlist .= '.';
		    $act .= '.';

		    $this->GetCh( );
		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( strpos( '*/-+!=', $this->m_chr ) !== false ) {

		    $fieldlist .= $this->m_chr;
		    $act .= $this->m_chr;

		    $this->GetCh( );
		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }


		} elseif ( $this->m_chr == '"' ) {

		    $fieldlist .= '"';
		    $act .= '"';
		    $this->GetCh( );

		    $str = $this->ScanUntilFolgezeichen( '"' );
		    $fieldlist .= $str;
		    $act .= $str;

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		}  elseif ( $this->m_chr == "'" ) {

		    $fieldlist .= "'";
		    $act .= "'";
		    $this->GetCh( );

		    $str = $this->ScanUntilFolgezeichen( "'" );
		    $fieldlist .= $str;
		    $act .= $str;

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		} elseif ( $this->m_chr == '(' ) {

		    $weiter = true;

		    $fieldlist .= '(';
		    $act .= '(';
		    $this->GetCh( );

		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

		    $statement = $this->ScanSubQuery( );

		    // $this->DumpStatementRest();

		    $fieldlist .= $statement;
		    $act .= $statement;

		    $this->SkipSpaces( );

		    // neu es folgt ein ',', ein 'AS' mit Alias, oder ein Alias ein FROM
		    $next = $this->NextIdentifier( );

		    if ( ( strtoupper( $next ) != 'FROM' ) && ( \rstoetter\libsqlphp\cMysql57_Utils::IsClauseStart( strtoupper( trim( $next ) ) ) ) ) {
			// $this->DumpStatementRest( );
			// echo( "<br> <b>SQL-Statement mit SubQuery ohne ALIAS!</b>" );
			$weiter = false;
		    }

		    if ( $weiter ) {

			if ( ( strtoupper( $next ) != 'FROM' ) && ( strlen( $next ) ) ) {

			    if ( strtoupper( $next ) == 'AS' ) {

				$fieldlist .= $next;
				$act .= ' ' . $next . ' ';
				$statement .= ' ' . $next . ' ' ;

				$this->ScanIdentifier( );	// skip AS
				$this->SkipSpaces( );

			    }

			    $alias = $this->ScanIdentifier( );

			    $fieldlist .= $alias;
			    $act .= ' ' . $alias . ' ';
			    // $statement .= ' ' . $next . ' ' ;
			    $this->SkipSpaces( );

			    $this->m_a_field_aliases[]= $alias;
			    // echo "<br> new alias found: $alias";

			}

			// echo "<br> new field ( subquery ) found: $act";

			$this->SkipSpaces( );

			$this->m_a_columns[]= trim( $act );
			$ary[] = trim( $act );

		    }

		    $this->SkipSpaces( );

		    $act = '';
		    $identifier = '';
		    $alias = '';
		    $next = '';
		    $statement = '';

		    if ( false ) {
			if ( mb_strtoupper( $this->NextIdentifier( ) ) == 'FROM' ) {

			    $this->DumpStatementRest();
			    echo $this->GetStatementHTML();

			}
		    }

		    // $this->DumpStatementRest( 'Rest nach subquery' );

		    // $identifier = $this->ScanTableOrFieldName( );


		}  elseif ( $this->is_ctype_identifier_start( $this->m_chr ) ) {

		    $pos = $this->m_char_index;
		    if ( $this-> SkipSpaces( ) ) { $fieldlist .= ' '; $act .= ' '; }

 		    $identifier = $this->ScanTableOrFieldName( );
 		    $this->SkipSpaces( );
		} else {
		    $fieldlist .= $identifier;
		    $act .= $identifier;
		    $identifier = $this->ScanTableOrFieldName( );
		    // echo "<br> scanne neuen identifier : $identifier";

		}

	    }	// while !fertig

if ( true ) {
 	    if ( strlen( trim( $act ) ) ) {

		// echo "<brScanFieldList: >addiere verwaistes act : $act";

		$ary[] = trim( $act );
 		$this->m_a_columns[]= trim( $act );
	    }
}

/*
	    if ( ( strlen( $identifier ) ) && ( strtolower( $identifier ) != 'from' ) ) {
 	    // if ( strlen( $identifier ) ) {

		$ary[] = trim( $identifier );
 		$this->m_a_columns[] = trim( $identifier );
 		$fieldlist .= ( strlen( $fieldlist) ? ',' : '') . trim( $identifier );
 		$pos = $this->m_char_index;
	    }
*/



 	    $act = '';

//	    $this->m_field_end = $pos;


	  if ( false ) {
	      echo "<br> leaving ScanFieldList() with ";
	      echo "<br> ary       =<br>"; var_dump ($ary);
	      echo "<br> fieldlist =<br>"; print_r ($fieldlist);
	      echo "<br> m_a_columns =<br>"; var_dump( $this->m_a_columns );
	      $this->DumpStatementRest( );
	      echo "<br> ---------------------- Ende ScanFieldList ";
	  }

	    return $fieldlist;

	}	// function ScanFieldList( )
	
    /**
      *
      * The method GetFieldsAsString( ) scans the actual query string and returns the field list which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @param string $str the comma seperated field list 
      *
      */   		
	


	public function GetFieldsAsString( string & $str ) {

	    // get a copy of the field array

	    // $ary = $this->m_a_columns;

	    $str = substr( $this->m_statement, $this->m_field_start, $this->m_field_len );

	}	// function GetFields( )
	
    /**
      *
      * The method AddField( ) adds a field to the field list and rescans the actual query string 
      *
      *
      *
      * Example:
      *
      * @param string $new_field the field to add to the field list
      *
      */   		
      
    public function AddField( string $new_field ) {  
    
        if ( strlen( trim( $new_field ) ) ) {
        
            $ary = array( );
            
            $this->GetFields( $ary );
            
            $ary[] = $new_field;
            
            $this->SetFields( $ary );
            
        }
    
    
    }   // function AddField( )
      
	
	
    /**
      *
      * The method GetFields( ) returns the field list which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @param array $ary the items of the field list as a string array
      *
      */   		
	

	public function GetFields( array & $ary ) {

	    // get a copy of the field array

	    $ary = $this->m_a_fields;	// extra gehandhabt, da ja subqueries vorkommen können, die auch Kommas enthalten

	    // $ary = $this->m_a_columns;
/*
echo "<br> field_start = $this->m_field_start und field len = $this->m_field_len";
	    if ( ! $this->m_field_len ) {
		$ary = array( );
	    } else {
		$ary = explode( ',', substr( $this->m_statement, $this->m_field_start, $this->m_field_len ) );
	    }
*/
	}	// function GetFields( )
	
    /**
      *
      * The method SetFields( ) sets the field list of tehe query 
      * The query will be rescanned
      *
      *
      * Example:
      *
      * @param mixed $ary the items of the field list as a string array or as a comma-seperated string
      *
      */   		
	

	public function SetFields( array $ary ) {

	    // set the field array new - string or array is allowed as parameter

	    // $this->Dump( );

	    if ( is_array( $ary ) ) $ary = implode( ',', $ary );

	    // echo "<br>SetFields() setting new field list = "; var_dump( explode( ',', $ary ) );

	    $str_org_fields = '';
	    $this->GetFieldsAsString( $str_org_fields );

	    $query = $this->m_statement;

	    // echo "<br> SetFields() mit ary ="; print_r( $ary );

	    $new_query = substr( $query, 0, $this->m_field_start - 1 );

	    $new_query .= ' ' . $ary . ' ';

	    $new_query .= ' ' . substr( $query, $this->m_field_start + $this->m_field_len );

	    $this->ScanStatement( $new_query, 'SELECT' );

	    // TODO: Anstatt von Auruf von ScanStatement( ) einfach die Zähler erhöhen ?



	}	// function SetFields( )
	
    /**
      *
      * The method RemoveWhereClause( ) removes the WHERE clause from the query 
      * The query will be rescanned
      *
      *
      * Example:
      *
      *
      */  	

    public function RemoveWhereClause( ) {

        if ( $this->m_where_start ) {
            $query = trim( substr( $this->m_statement, 0, $this->m_where_start - 1 ) );
            $query = trim( substr( $query, 0, strlen( $query ) - strlen( 'where' ) ) );

            $this->ScanStatement( $query, 'SELECT' );
        }

        // return $this->m_where_clause;

    }	// function RemoveWhereClause( )


    /**
      *
      * The method ActChar( ) returns the character from the query, which was read by GetCh( )
      *
      * @return string the actual char the scanner is working on
      *
      * Example:
      *
      */     

	protected function ActChar( ) : string {

	    if ( $this->m_char_index == -1 ) return '';

	    return substr( $this->m_statement, $this->m_char_index, 1 );

	}	// function ActChar( );
	
	
    /**
      *
      * The method ScanLimitCondition( ) scans the actual query string and returns the next LIMIT clause which is in the buffer      
      *
      *
      *
      * Example:
      *
      * @param array $ary_limit the items of the LIMIT clasue in a string array
      *
      * @return string the LIMIT clause as comma-seperated string
      *
      */   		
	

	private function ScanLimitCondition( array & $ary_limit ) : string {

	    //  echo "<br> ScanLimitCondition( )";

	    $ary_limit = array( );

	    $this->SkipSpaces( );

	    $komma = '';
	    $limit2 = '';
	    $limit1 = $this->ScanNumber( );

	    $this->SkipSpaces( );

	    if ( $this->m_chr == ',' ) {

		$this->GetCh( );
		$komma = ',';
		$this->SkipSpaces( );
		$limit2 = $this->ScanNumber( );
		$this->SkipSpaces( );

	    } else {

	    }

	    $limit = $limit1 . $komma . $limit2;

	    //  echo "<br> ScanLimitCondition liefert->$limit1 und $limit2";

	    $ary_limit = array( $limit1, $limit2 );

	    return $limit;


	}	// function ScanLimitCondition( )

}	// class cSmartSqlStatement( )

?>
