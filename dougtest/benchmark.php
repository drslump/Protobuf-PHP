<?php

require_once 'Benchmark/Profiler.php';
require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/MysqlQueryResult.lazy.php';


class Benchmark {

    protected $tests = array(
        'DecodeExtBinaryLazyDoug',
        'DecodeExtBinaryDoug',
        'DecodeExtBinaryArrayDoug',
        'DecodeBinaryLazyDoug',
        'DecodeJsonLazyDoug',
        'DecodeBinaryDoug',
        'DecodeJsonDoug',
        'DecodeRawJsonDoug',
    );

    public function run($iterations = 1000)
    {
        $profiler = new Benchmark_Profiler(true);
        foreach ($this->tests as $test) {
            $method = 'config' . $test;
            $args = $this->$method();

            $method = 'run' . $test;
            $profiler->enterSection($test);
            for ($i=0; $i<$iterations; $i++) {
                call_user_func_array(array($this, $method), $args);
            }
            $profiler->leaveSection($test);
        }

        $profiler->stop();
        $profiler->display();
    }

    protected function printDoug($msg, $label)
    {
        static $done = array();

        return;

        // Print only once per label
        $print = !in_array($label, $done);
        $done[] = $label;

        if (is_array($msg)) { 
            $msg = json_decode(json_encode($msg));
        }

        $print && print("\n<<<< $label >>>>\n");
        foreach ($msg->resultset as $rs) {
            $print && print("Number of rows: " . count($rs->row) . PHP_EOL);
            for ($i=0; $i<10; $i++) {
                $row = $rs->row[$i];
                for ($j=0; $j<5; $j++) {
                    $print && print($row->column[$j]->value . ' | ');
                }
                $print && print("\n");
            }
            $print && print("==========================================================\n");
        }
    }


    protected function configDecodeExtBinaryLazyDoug()
    {
        return array(
            new Protobuf\Codec\ExtBinary(),
            file_get_contents(__DIR__ . '/doug.pb')
        );
    }

    protected function runDecodeExtBinaryLazyDoug($codec, $data)
    {
        $out = $codec->decode(new requestd\MysqlQueryResult(), $data);
        $this->printDoug($out, 'ExtBinaryLazy');
    }

    protected function configDecodeExtBinaryArrayDoug()
    {
        return array(
            new Protobuf\Codec\ExtBinary(false),
            file_get_contents(__DIR__ . '/doug.pb')
        );
    }

    protected function runDecodeExtBinaryArrayDoug($codec, $data)
    {
        $out = $codec->decodeAsArray(new requestd\MysqlQueryResult(), $data);
        $this->printDoug($out, 'ExtBinaryArray');
    }

    protected function configDecodeExtBinaryDoug()
    {
        return array(
            new Protobuf\Codec\ExtBinary(false),
            file_get_contents(__DIR__ . '/doug.pb')
        );
    }

    protected function runDecodeExtBinaryDoug($codec, $data)
    {
        $out = $codec->decode(new requestd\MysqlQueryResult(), $data);
        $this->printDoug($out, 'ExtBinary');
    }

    protected function configDecodeBinaryLazyDoug()
    {
        return array(
            new Protobuf\Codec\LazyBinary(),
            file_get_contents(__DIR__ . '/doug.pb')
        );
    }

    protected function runDecodeBinaryLazyDoug($codec, $data)
    {
        $out = $codec->decode(new requestd\MysqlQueryResult(), $data);
        $this->printDoug($out, 'LazyBinary');
    }

    protected function configDecodeBinaryDoug()
    {
        return array(
            new Protobuf\Codec\LazyBinary(false),
            file_get_contents(__DIR__ . '/doug.pb')
        );
    }

    protected function runDecodeBinaryDoug($codec, $data)
    {
        $out = $codec->decode(new requestd\MysqlQueryResult(), $data);
        $this->printDoug($out, 'Binary');
    }


    protected function configDecodeJsonLazyDoug()
    {
        $codecBin = new Protobuf\Codec\LazyBinary();
        $codecJson = new Protobuf\Codec\Json();

        $bin = $this->configDecodeBinaryDoug();
        $simple = $codecBin->decode(new requestd\MysqlQueryResult(), $bin[1]);
        $data = $codecJson->encode($simple);
        return array($codecJson, $data);
    }

    protected function runDecodeJsonLazyDoug($codec, $data)
    {
        $out = $codec->decode(new requestd\MysqlQueryResult(), $data);
        $this->printDoug($out, 'LazyJson');
    }

    protected function configDecodeJsonDoug()
    {
        $codecBin = new Protobuf\Codec\LazyBinary();
        $codecJson = new Protobuf\Codec\Json(false);

        $bin = $this->configDecodeBinaryDoug();
        $simple = $codecBin->decode(new requestd\MysqlQueryResult(), $bin[1]);
        $data = $codecJson->encode($simple);
        return array($codecJson, $data);
    }


    protected function runDecodeJsonDoug($codec, $data)
    {
        $out = $codec->decode(new requestd\MysqlQueryResult(), $data);
        $this->printDoug($out, 'Json');
    }



    protected function configDecodeRawJsonDoug()
    {
        return $this->configDecodeJsonDoug();
    }

    protected function runDecodeRawJsonDoug($codec, $data)
    {
        $out = json_decode($data);
        $this->printDoug($out, 'RawJson');
    }
}


if (function_exists('xhprof_enable')) {
    xhprof_enable();
}


$bench = new Benchmark();
$bench->run(10);


if (function_exists('xhprof_enable')) {
    $data = xhprof_disable();

    include_once "xhprof_lib/utils/xhprof_lib.php";
    include_once "xhprof_lib/utils/xhprof_runs.php";

    $xhprof = new XHProfRuns_Default();

    // Save the run under a namespace "xhprof".
    $run_id = $xhprof->save_run($data, "xhprof");
    echo "\nXHPROF: $run_id\n";
}
