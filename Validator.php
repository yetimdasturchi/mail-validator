<?php

class Validator {

	private $email;
	protected $from;

	protected $allow_comments = TRUE;
	protected $public_internet = TRUE;

	protected $stream = FALSE;
    protected $port = 25;
    protected $max_connection_timeout = 30;
    protected $stream_timeout = 5;
    protected $stream_timeout_wait = 0;
    protected $exceptions = TRUE;
    protected $error_count = 0;
    protected $crlf = "\r\n";
	
	function __construct( $email = NULL ) {
		if ( ! is_null( $email ) ) {
			$this->setEmail( $email );
		}
	}

	public function setEmail( $email ){
		$this->email = $email;
		return $this;
	}

	public function setConnectionTimeout( $seconds ) {
        if ( $seconds > 0 ) {
            $this->max_connection_timeout = (int) $seconds;
        }
    }

    public function setStreamTimeout( $seconds ) {
        if ( $seconds > 0 ) {
            $this->stream_timeout = (int) $seconds;
        }
    }

    public function setStreamTimeoutWait( $seconds ) {
        if ( $seconds >= 0 ) {
            $this->stream_timeout_wait = (int) $seconds;
        }
    }

    public function getMXrecords( $hostname = NULL ) {

    	if ( is_null( $hostname ) ) {
    		$hostname = $this->parse_email( $this->email );
    	}

        $mxhosts = [];
        $mxweights = [];

        if ( getmxrr( $hostname, $mxhosts, $mxweights ) === FALSE ) {
            if ( $this->exceptions ) {
            	throw new Exception( 'MX records not found or an error occurred' );
            }

            return [];

        }else{
            array_multisort($mxweights, $mxhosts);
        }
        
        if ( empty( $mxhosts ) ) {
            $mxhosts[] = $hostname;
        }
        return $mxhosts;
    }

    public static function parse_email( $email, $only_domain = TRUE ) {
        sscanf( $email, "%[^@]@%s", $user, $domain );
        return ( $only_domain ) ? $domain : [ $user, $domain ];
    }

    public function check( $email = NULL ) {
		
		if ( is_null( $email ) ) {
			$email = $this->email;
		}

        $result = FALSE;

        if ( !$this->isValid( $email ) ) {
        	if ( $this->exceptions ) {
            	throw new Exception( "Incorrect address" );
			}

            return FALSE;
        }

        $this->stream = FALSE;

        $domain = $this->parse_email( $email );
        $this->from = $this->generateUsername() . '@' . $domain;
		$mxs = $this->getMXrecords( $domain );
        $timeout = ceil( $this->max_connection_timeout / count( $mxs ) );

        $ctx = stream_context_create();
        
		stream_context_set_option($ctx, 'ssl', 'verify_peer', false);
		stream_context_set_option($ctx, 'ssl', 'verify_peer_name', false);

        foreach ( $mxs as $host ) {
        	$this->stream = @stream_socket_client( "tcp://" . $host . ":" . $this->port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx );
            if ( $this->stream === FALSE ) {
                if ( $errno == 0 ) {
                    
                    if ($this->exceptions) {
                        throw new Exception( "Problem initializing the socket" );
                    }

                    return FALSE;
                }
            } else {
                stream_set_timeout( $this->stream, $this->stream_timeout );
                stream_set_blocking( $this->stream, 1 );

                if ( $code = $this->_streamCode( fread( $this->stream, 1024 ) ) != '220' ) {
                    fclose( $this->stream );
                    $this->stream = FALSE;
                }else{
                	break;
                }
            }
        }

        if ( $this->stream === FALSE ) {
            
            if ($this->exceptions) {
            	throw new Exception( "Connection fail" );
			}

            return FALSE;
        }

        fread( $this->stream, 1024 );
        fwrite( $this->stream, "EHLO {$host}\r\n" );
        fread( $this->stream,1024);

        fwrite( $this->stream, "STARTTLS\r\n" );
        fread(  $this->stream, 1024 );

        stream_socket_enable_crypto( $this->stream, TRUE, STREAM_CRYPTO_METHOD_SSLv23_CLIENT );

        fwrite( $this->stream, "EHLO {$host}\r\n" );
        fread( $this->stream, 1024 );

        fwrite( $this->stream, "mail from: <{$this->from}>\r\n" );
        fread( $this->stream, 1024 );

        fwrite( $this->stream, "rcpt to: <{$email}>\r\n" );
        
        $code = $this->_streamCode( fread( $this->stream, 8192 ) );

        // stream_socket_enable_crypto($fp, false);

        fclose( $this->stream );

        switch ( $code ) {
            case '250':
            case '450':
            case '451':
            case '452':
                return TRUE;
            default :
                return FALSE;
        }
    }

    protected function _streamCode( $str ) {
        preg_match( '/^(?<code>[0-9]{3})(\s|-)(.*)$/ims', $str, $matches );
        return isset( $matches['code'] ) ? $matches['code'] : FALSE;
    }

    function randomName() {
    	static $firstname = [
	        'Johnathon',
	        'Anthony',
	        'Erasmo',
	        'Raleigh',
	        'Nancie',
	        'Tama',
	        'Camellia',
	        'Augustine',
	        'Christeen',
	        'Luz',
	        'Diego',
	        'Lyndia',
	        'Thomas',
	        'Georgianna',
	        'Leigha',
	        'Alejandro',
	        'Marquis',
	        'Joan',
	        'Stephania',
	        'Elroy',
	        'Zonia',
	        'Buffy',
	        'Sharie',
	        'Blythe',
	        'Gaylene',
	        'Elida',
	        'Randy',
	        'Margarete',
	        'Margarett',
	        'Dion',
	        'Tomi',
	        'Arden',
	        'Clora',
	        'Laine',
	        'Becki',
	        'Margherita',
	        'Bong',
	        'Jeanice',
	        'Qiana',
	        'Lawanda',
	        'Rebecka',
	        'Maribel',
	        'Tami',
	        'Yuri',
	        'Michele',
	        'Rubi',
	        'Larisa',
	        'Lloyd',
	        'Tyisha',
	        'Samatha',
	    ];

	    static $lastname = [
	        'Mischke',
	        'Serna',
	        'Pingree',
	        'Mcnaught',
	        'Pepper',
	        'Schildgen',
	        'Mongold',
	        'Wrona',
	        'Geddes',
	        'Lanz',
	        'Fetzer',
	        'Schroeder',
	        'Block',
	        'Mayoral',
	        'Fleishman',
	        'Roberie',
	        'Latson',
	        'Lupo',
	        'Motsinger',
	        'Drews',
	        'Coby',
	        'Redner',
	        'Culton',
	        'Howe',
	        'Stoval',
	        'Michaud',
	        'Mote',
	        'Menjivar',
	        'Wiers',
	        'Paris',
	        'Grisby',
	        'Noren',
	        'Damron',
	        'Kazmierczak',
	        'Haslett',
	        'Guillemette',
	        'Buresh',
	        'Center',
	        'Kucera',
	        'Catt',
	        'Badon',
	        'Grumbles',
	        'Antes',
	        'Byron',
	        'Volkman',
	        'Klemp',
	        'Pekar',
	        'Pecora',
	        'Schewe',
	        'Ramage',
	    ];

	    $name = $firstname[ rand ( 0 , count( $firstname ) -1 ) ];
	    $name .= ' ';
	    $name .= $lastname[ rand ( 0 , count( $lastname ) -1 ) ];

	    return $name;
    }

    protected function generateUsername() {
    	$name = $this->randomName();

    	$removedMultispace = preg_replace( '/\s+/', ' ', $name );
    	$sanitized = preg_replace( '/[^A-Za-z0-9\ ]/', '', $removedMultispace );
    	$lowercased = strtolower( $sanitized );
    	$splitted = explode( " ", $lowercased );

    	if ( count( $splitted ) == 1) {
    		$username = substr( $splitted[0], 0, rand( 3, 6 ) ) . ( mt_rand( 0, 2 ) == 1 ? rand( 10, 9999 ) : '_' . rand( 10, 9999 ) );
    	} else {
    		$username = $splitted[0] . substr( $splitted[1], 0, rand( 0, 4 ) ) . rand( 10, 9999 );
    	}

    	return $username;
    }

	public function isValid( $email = NULL ){

		if ( is_null( $email ) ) {
			$email = $this->email;
		}

		return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	public function isRfcValid( $email = NULL ){

		if ( is_null( $email ) ) {
			$email = $this->email;
		}

		$no_ws_ctl	= "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
		$alpha		= "[\\x41-\\x5a\\x61-\\x7a]";
		$digit		= "[\\x30-\\x39]";
		$cr			= "\\x0d";
		$lf			= "\\x0a";
		$crlf		= "(?:$cr$lf)";


		$obs_char	= "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
		$obs_text	= "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
		$text		= "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";

		$text		= "(?:$lf*$cr*$obs_char$lf*$cr*)";
		$obs_qp		= "(?:\\x5c[\\x00-\\x7f])";
		$quoted_pair	= "(?:\\x5c$text|$obs_qp)";


		$wsp		= "[\\x20\\x09]";
		$obs_fws	= "(?:$wsp+(?:$crlf$wsp+)*)";
		$fws		= "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
		$ctext		= "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
		$ccontent	= "(?:$ctext|$quoted_pair)";
		$comment	= "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
		$cfws		= "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";


		$outer_ccontent_dull	= "(?:$fws?$ctext|$quoted_pair)";
		$outer_ccontent_nest	= "(?:$fws?$comment)";
		$outer_comment		= "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";


		$atext		= "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
		$atom		= "(?:$cfws?(?:$atext)+$cfws?)";


		$qtext		= "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
		$qcontent	= "(?:$qtext|$quoted_pair)";
		$quoted_string	= "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";

		$quoted_string	= "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
		$word		= "(?:$atom|$quoted_string)";


		$obs_local_part	= "(?:$word(?:\\x2e$word)*)";
		$obs_domain	= "(?:$atom(?:\\x2e$atom)*)";


		$dot_atom_text	= "(?:$atext+(?:\\x2e$atext+)*)";
		$dot_atom	= "(?:$cfws?$dot_atom_text$cfws?)";


		$dtext		= "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
		$dcontent	= "(?:$dtext|$quoted_pair)";
		$domain_literal	= "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";


		$local_part	= "(($dot_atom)|($quoted_string)|($obs_local_part))";
		$domain		= "(($dot_atom)|($domain_literal)|($obs_domain))";
		$addr_spec	= "$local_part\\x40$domain";



		if (strlen($email) > 254) return FALSE;


		if ( $this->allow_comments ){
			$email = $this->email_strip_comments( $outer_comment, $email, "(x)" );
		}


		if ( ! preg_match( "!^$addr_spec$!", $email, $m ) ) return FALSE;
		
		$bits = [
			'local'				=> isset($m[1]) ? $m[1] : '',
			'local-atom'		=> isset($m[2]) ? $m[2] : '',
			'local-quoted'		=> isset($m[3]) ? $m[3] : '',
			'local-obs'			=> isset($m[4]) ? $m[4] : '',
			'domain'			=> isset($m[5]) ? $m[5] : '',
			'domain-atom'		=> isset($m[6]) ? $m[6] : '',
			'domain-literal'	=> isset($m[7]) ? $m[7] : '',
			'domain-obs'		=> isset($m[8]) ? $m[8] : '',
		];


		if ( $this->allow_comments ){
			$bits['local']	= $this->email_strip_comments($comment, $bits['local']);
			$bits['domain']	= $this->email_strip_comments($comment, $bits['domain']);
		}


		if (strlen($bits['local']) > 64) return FALSE;
		if (strlen($bits['domain']) > 255) return FALSE;


		if (strlen($bits['domain-literal'])){

			$Snum			= "(\d{1,3})";
			$IPv4_address_literal	= "$Snum\.$Snum\.$Snum\.$Snum";

			$IPv6_hex		= "(?:[0-9a-fA-F]{1,4})";

			$IPv6_full		= "IPv6\:$IPv6_hex(?:\:$IPv6_hex){7}";

			$IPv6_comp_part		= "(?:$IPv6_hex(?:\:$IPv6_hex){0,7})?";
			$IPv6_comp		= "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";

			$IPv6v4_full		= "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";

			$IPv6v4_comp_part	= "$IPv6_hex(?:\:$IPv6_hex){0,5}";
			$IPv6v4_comp		= "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";


			if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)){
				if (intval($m[1]) > 255) return FALSE;
				if (intval($m[2]) > 255) return FALSE;
				if (intval($m[3]) > 255) return FALSE;
				if (intval($m[4]) > 255) return FALSE;
			}else{
				while (1){
					if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])){
						break;
					}

					if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)){
						list($a, $b) = explode('::', $m[1]);
						$folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
						$groups = explode(':', $folded);
						if (count($groups) > 7) return FALSE;
						break;
					}

					if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)){

						if (intval($m[1]) > 255) return FALSE;
						if (intval($m[2]) > 255) return FALSE;
						if (intval($m[3]) > 255) return FALSE;
						if (intval($m[4]) > 255) return FALSE;
						break;
					}

					if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)){
						list($a, $b) = explode('::', $m[1]);
						$b = substr($b, 0, -1);
						$folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
						$groups = explode(':', $folded);
						if (count($groups) > 5) return FALSE;
						break;
					}

					return FALSE;
				}
			}			
		}else{
			$labels = explode('.', $bits['domain']);


			if ( $this->public_internet ){
				if (count($labels) == 1) return FALSE;
			}


			foreach ($labels as $label){
				if (strlen($label) > 63) return FALSE;
				if (substr($label, 0, 1) == '-') return FALSE;
				if (substr($label, -1) == '-') return FALSE;
			}


			if ( $this->public_internet ){
				if ( preg_match( '!^[0-9]+$!', array_pop( $labels ) ) ) return FALSE;
			}
		}

		return TRUE;
	}

	function email_strip_comments( $comment, $email, $replace='' ){
		while (1){
			$new = preg_replace( "!$comment!", $replace, $email );
			
			if ( strlen( $new ) == strlen( $email ) ){
				return $email;
			}

			$email = $new;
		}
	}
}
