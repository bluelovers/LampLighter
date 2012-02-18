<?php

class QueryParse {

    public static $querysections = array('alter', 'create', 'drop', 'select', 'delete', 'insert', 'update','from','where','limit','order');
    public static $operators = array('=', '<>', '<', '<=', '>', '>=', 'like', 'clike', 'slike', 'not', 'is', 'in', 'between');
    public static $types = array('character', 'char', 'varchar', 'nchar', 'bit', 'numeric', 'decimal', 'dec', 'integer', 'int', 'smallint', 'float', 'real', 'double', 'date', 'datetime', 'time', 'timestamp', 'interval', 'bool', 'boolean', 'set', 'enum', 'text');
    public static $conjuctions = array('by', 'as', 'on', 'into', 'from', 'where', 'with');
    public static $funcitons = array('avg', 'count', 'max', 'min', 'sum', 'nextval', 'currval', 'concat');
    public static $reserved = array('absolute', 'action', 'add', 'all', 'allocate', 'and', 'any', 'are', 'asc', 'ascending', 'assertion', 'at', 'authorization', 'begin', 'bit_length', 'both', 'cascade', 'cascaded', 'case', 'cast', 'catalog', 'char_length', 'character_length', 'check', 'close', 'coalesce', 'collate', 'collation', 'column', 'commit', 'connect', 'connection', 'constraint', 'constraints', 'continue', 'convert', 'corresponding', 'cross', 'current', 'current_date', 'current_time', 'current_timestamp', 'current_user', 'cursor', 'day', 'deallocate', 'declare', 'default', 'deferrable', 'deferred', 'desc', 'descending', 'describe', 'descriptor', 'diagnostics', 'disconnect', 'distinct', 'domain', 'else', 'end', 'end-exec', 'escape', 'except', 'exception', 'exec', 'execute', 'exists', 'external', 'extract', 'false', 'fetch', 'first', 'for', 'foreign', 'found', 'full', 'get', 'global', 'go', 'goto', 'grant', 'group', 'having', 'hour', 'identity', 'immediate', 'indicator', 'initially', 'inner', 'input', 'insensitive', 'intersect', 'isolation', 'join', 'key', 'language', 'last', 'leading', 'left', 'level', 'limit', 'local', 'lower', 'match', 'minute', 'module', 'month', 'names', 'national', 'natural', 'next', 'no', 'null', 'nullif', 'octet_length', 'of', 'only', 'open', 'option', 'or', 'order', 'outer', 'output', 'overlaps', 'pad', 'partial', 'position', 'precision', 'prepare', 'preserve', 'primary', 'prior', 'privileges', 'procedure', 'public', 'read', 'references', 'relative', 'restrict', 'revoke', 'right', 'rollback', 'rows', 'schema', 'scroll', 'second', 'section', 'session', 'session_user', 'size', 'some', 'space', 'sql', 'sqlcode', 'sqlerror', 'sqlstate', 'substring', 'system_user', 'table', 'temporary', 'then', 'timezone_hour', 'timezone_minute', 'to', 'trailing', 'transaction', 'translate', 'translation', 'trim', 'true', 'union', 'unique', 'unknown', 'upper', 'usage', 'user', 'using', 'value', 'values', 'varying', 'view', 'when', 'whenever', 'work', 'write', 'year', 'zone', 'eoc');
    public static $startparens = array('{', '(');
    public static $endparens = array('}', ')');
    public static $tokens = array(',', ' ');
    
    private $query;

	public static function Strip_sql_clause( $clause, $clause_type = NULL ) {

		LL::Require_class('SQL/QueryConstants');

	        //$clause = preg_replace('/^\s*ON\s+/i', '', $clause);

	      if ( !$clause_type || ($clause_type == QueryConstants::CLAUSE_SELECT) ) {
		        $clause = preg_replace('/^\s*SELECT\s+/i', '', $clause);	
		}
		
	        if ( !$clause_type || ($clause_type == QueryConstants::CLAUSE_WHERE) ) {
			$clause = preg_replace('/^\s*WHERE\s+/i', '', $clause);
		}

	        if ( !$clause_type || ($clause_type == QueryConstants::CLAUSE_FROM) ) {
		        $clause = preg_replace('/^\s*FROM\s+/i', '', $clause);	
		}

	        if ( !$clause_type || ($clause_type == QueryConstants::CLAUSE_LIMIT) ) {
	        	$clause = preg_replace('/^\s*LIMIT\s+/i', '', $clause);
		}

	        if ( !$clause_type || ($clause_type == QueryConstants::CLAUSE_ORDER_BY) ) {
		        $clause = preg_replace('/^\s*ORDER\s+BY\s+/i', '', $clause);
		}
                                
        	return $clause;
	}
	


 	/**
     * Simple SQL Tokenizer
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license GPL
     * @param string $sqlQuery
     * @return token array
     */
    public static function Tokenize($sqlQuery, $cleanWhitespace = false) {
       
        /**
         * Strip extra whitespace from the query
         */
        if($cleanWhitespace) {
         $sqlQuery = ltrim(preg_replace('/[\\s]{2,}/',' ',$sqlQuery));
        }
               
        /**
         * Regular expression based on SQL::Tokenizer's Tokenizer.pm by Igor Sutton Lopes
         **/
        $regex = '('; # begin group
        $regex .= '(?:--|\\#)[\\ \\t\\S]*'; # inline comments
        $regex .= '|(?:<>|<=>|>=|<=|==|=|!=|!|<<|>>|<|>|\\|\\||\\||&&|&|-|\\+|\\*(?!\/)|\/(?!\\*)|\\%|~|\\^|\\?)'; # logical operators
        $regex .= '|[\\[\\]\\(\\),;`]|\\\'\\\'(?!\\\')|\\"\\"(?!\\"")'; # empty single/double quotes
        $regex .= '|".*?(?:(?:""){1,}"|(?<!["\\\\])"(?!")|\\\\"{2})|\'.*?(?:(?:\'\'){1,}\'|(?<![\'\\\\])\'(?!\')|\\\\\'{2})'; # quoted strings
        $regex .= '|\/\\*[\\ \\t\\n\\S]*?\\*\/'; # c style comments
        $regex .= '|(?:[\\w:@]+(?:\\.(?:\\w+|\\*)?)*)'; # words, placeholders, database.table.column strings
        $regex .= '|[\t\ ]+';
        $regex .= '|[\.]'; #period
       
        $regex .= ')'; # end group
       
        // get global match
        preg_match_all( '/' . $regex . '/smx', $sqlQuery, $result );
       
        // return tokens
        return $result[0];
   
    }

    /**
     * Simple SQL Parser
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @param string $sqlQuery
     * @param bool optional $cleanup
     * @return SqlParser Object
     */
    public static function Parse_string($sqlQuery) {
       
        // returns a SqlParser object
        $handle = ( isset($this) ) ? $this : new QueryParse();
        
        // copy and cut the query
        $tokens = self::Tokenize( $sqlQuery );
        $tokenCount = count( $tokens );
        $queryParts = array();
        $section = $tokens[0];
       
        // parse the tokens
        for ($t = 0; $t < $tokenCount; $t ++) {
           
            if (in_array( $tokens[$t], self::$startparens )) {
               
                $sub = $handle->readsub( $tokens, $t );
                $handle->query[$section].= $sub;
               
            } else {
               
                if(in_array(strtolower($tokens[$t]),self::$querysections) && !isset($handle->query[$tokens[$t]])) {
                    $section = strtolower($tokens[$t]);
                }
               
                // rebuild the query in sections
                if ( !isset($handle->query[$section]) || ($handle->query[$section]=='') ) $handle->query[$section] = '';
                $handle->query[$section] .= $tokens[$t];  
               
            }                      
            
        }

        return $handle;
   
    }
  
     /**
     * Parses a section of a query ( usually a sub-query or where clause )
     *
     * @param array $tokens
     * @param int $position
     * @return string section
     */
    private function readsub($tokens, &$position) {
       
        $sub = $tokens[$position];
        $subs = 0;
        $tokenCount = count( $tokens );
        $position ++;
        while ( ! in_array( $tokens[$position], self::$endparens ) && $position < $tokenCount ) {
           
            if (in_array( $tokens[$position], self::$startparens )) {
                $sub.= $this->readsub( $tokens, $position );
                $subs++;
            } else {
                $sub.= $tokens[$position];
            }
            $position ++;
        }
        $sub.= $tokens[$position];      
        return $sub;
    }
       
       
    /**
     * Returns manipulated sql to get the number of rows in the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    public function get_count_query() {
       
        $this->query['select'] = 'SELECT COUNT(*) AS count ';
        unset($this->query['limit']);
        #die(implode('',$this->query));
        return implode('',$this->query);
       
    }
   
    /**
     * Returns manipulated sql to get the unlimited number of rows in the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    public function get_limited_count_query() {
       
        $this->query['select'] = 'SELECT COUNT(*) AS count ';
        return implode('',$this->query);
       
    }
   
    /**
     * Returns the where section of the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
   	 public function get($which) {
       
        if(!isset($this->query[$which])) return false;
        return $this->query[$which];
       
    }
         
    /**
     * Returns the where section of the query.
     *
     * @author Justin Carlson <justin.carlson@gmail.com>
     * @license LGPL
     * @return string sql
     */
    public function get_array( ) {
       
        return $this->query;
       
    }


}


?>