#!/usr/bin/perl

use CGI qw/ :standard /;
use LWP::UserAgent;

# my $spellercss = '/speller/spellerStyle.css';					# by FredCK
my $spellercss = '../spellerStyle.css';							# by FredCK
# my $wordWindowSrc = '/speller/wordWindow.js';					# by FredCK
my $wordWindowSrc = '../wordWindow.js';							# by FredCK
my @textinputs = param( 'textinputs[]' ); # array

my $URL = "http://speller.yandex.net/services/yspell";
my $lang = 'ru,en';
my $options = 4; # IGNORE_URLS
my $input_separator = "A";

# set the 'wordtext' JavaScript variable to the submitted text.
sub printTextVar {
	for( my $i = 0; $i <= $#textinputs; $i++ ) {
		print "textinputs[$i] = decodeURIComponent('" . escapeQuote( $textinputs[$i] ) . "')\n";
	}
}

sub printTextIdxDecl {
	my $idx = shift;
	print "words[$idx] = [];\n";
	print "suggs[$idx] = [];\n";
}

sub printWordsElem {
	my( $textIdx, $wordIdx, $word ) = @_;
	print "words[$textIdx][$wordIdx] = '" . escapeQuote( $word ) . "';\n";
}

sub printSuggsElem {
	my( $textIdx, $wordIdx, @suggs ) = @_;
	print "suggs[$textIdx][$wordIdx] = [";
	for my $i ( 0..$#suggs ) {
		print "'" . escapeQuote( $suggs[$i] ) . "'";
		if( $i < $#suggs ) {
			print ", ";
		}
	}
	print "];\n";
}

sub printCheckerResults {
	my $textInputIdx = -1;
	my $wordIdx = 0;
	my $unhandledText;
	my $requestText = "";

	# add the submitted text.
	for( my $i = 0; $i <= $#textinputs; $i++ ) {
		$text = url_decode( $textinputs[$i] );
		# Strip all tags for the text. (by FredCK - #339 / #681)
		$text =~ s/<[^>]+>/ /g;
		@lines = split( /\n/, $text );
		$requestText .= "\%\n"; # exit terse mode
		$requestText .= "^$input_separator\n";
		$requestText .= "!\n";  # enter terse mode
		for my $line ( @lines ) {
			# use carat on each line to escape possible aspell commands
			$requestText .= "^$line\n";
		}
	}

	# exec yspell request
	my $resultText = sendRequest( $requestText );
	if (not $resultText) {
		return;
	}

	# parse each line of aspell return
	for my $ret (split ( "\n", $resultText )) {
		chomp( $ret );
		# if '&', then not in dictionary but has suggestions
		# if '#', then not in dictionary and no suggestions
		# if '*', then it is a delimiter between text inputs
		if( $ret =~ /^\*/ ) {
			$textInputIdx++;
			printTextIdxDecl( $textInputIdx );
			$wordIdx = 0;

		} elsif( $ret =~ /^(&|#)/ ) {
			my @tokens = split( " ", $ret, 5 );
			printWordsElem( $textInputIdx, $wordIdx, $tokens[1] );
			my @suggs = ();
			if( $tokens[4] ) {
				@suggs = split( ", ", $tokens[4] );
			}
			printSuggsElem( $textInputIdx, $wordIdx, @suggs );
			$wordIdx++;
		} else {
			$unhandledText .= $ret;
		}
	}
}

sub escapeQuote {
	my $str = shift;
	$str =~ s/'/\\'/g;
	return $str;
}

sub handleError {
	my $err = shift;
	print "error = '" . escapeQuote( $err ) . "';\n";
}

sub url_decode {
	local $_ = @_ ? shift : $_;
	defined or return;
	# change + signs to spaces
	tr/+/ /;
	# change hex escapes to the proper characters
	s/%([a-fA-F0-9]{2})/pack "H2", $1/eg;
	return $_;
}

sub sendRequest {
	my $text = shift;

	my $url = "$URL?options=$options&lang=$lang&mode=html";

	my $ua = new LWP::UserAgent;
	my $request = new HTTP::Request("POST", $url);
	$request->header("Content-Type" => "text/plain; charset=UTF-8");
	$request->content($text);
	my $response = $ua->request($request);

	if ($response->code != 200) {
		handleError( "$URL: " . $response->content . "\n" );
		return "";
	}

	return $response->content;
}

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# Display HTML
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

print <<EOF;
Content-type: text/html; charset=utf-8

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="$spellercss"/>
<script src="$wordWindowSrc"></script>
<script type="text/javascript">
var suggs = new Array();
var words = new Array();
var textinputs = new Array();
var error;
EOF

printTextVar();

printCheckerResults();

print <<EOF;
var wordWindowObj = new wordWindow();
wordWindowObj.originalSpellings = words;
wordWindowObj.suggestions = suggs;
wordWindowObj.textInputs = textinputs;


function init_spell() {
	// check if any error occured during server-side processing
	if( error ) {
		alert( error );
	} else {
		// call the init_spell() function in the parent frameset
		if (parent.frames.length) {
			parent.init_spell( wordWindowObj );
		} else {
			error = "This page was loaded outside of a frameset. ";
			error += "It might not display properly";
			alert( error );
		}
	}
}

</script>

</head>
<body onLoad="init_spell();">

<script type="text/javascript">
wordWindowObj.writeBody();
</script>

</body>
</html>
EOF
