<?
require_once( 'DrSlump/Protobuf.php' );
DrSlump\Protobuf::autoload();

require_once( 'MysqlQueryResult.pb.php' );

$data = file_get_contents( '/tmp/pb-1.pb' );

$pb = new requestd\MysqlQueryResult;

$t1 = microtime( true );
$pb->parse( $data );
$t2 = microtime( true );

echo "Time to parse ".strlen( $data )." PB bytes: ".( $t2 - $t1 )." seconds.\n";

//print_r( $pb );

?>
