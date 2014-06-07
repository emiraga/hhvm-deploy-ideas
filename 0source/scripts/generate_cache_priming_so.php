<?hh
// Copyright 2004-present Facebook. All Rights Reserved.

function get_all_cache_data() {
  $data1 = array (
    // These values will be available in apc, via:
    //
    //    apc_fetch('val1'), apc_fetch('val2')
    //
    'val1' => array (
      30,
      60,
      140,
    ),
    'val2' => array (
      150,
    ),
  );

  $data2 = array (
    // Also, available via apc_fetch :)
    //
    'cache1' => "test",
  );

  return array (
    'data1' => $data1,
    'data2' => $data2,
  );
}

function incr(&$arr, $idx, $value = 1) {
  if (!isset($arr[$idx])) {
    $arr[$idx] = 0;
  }
  $arr[$idx] += $value;
  return $arr[$idx];
}

abstract class CachePrimingSerializer {

  /**
   *  @return string Readable name of serialization format.
   */
  abstract public function getName();

  /**
   *  @param  array  The php datastructure to serialize.
   *  @return string The binary string serialization.
   */
  abstract public function serialize($data);

  /**
   *  @return CachePrimingSerializer A subclass that implements that intended
   *                                 format.
   */
  public static function newSerializer($archive_name) {
    return new CachePrimingHphpSerializer($archive_name);
  }
}


/**
 * Parts of this class are copied from:
 *
 * https://github.com/facebook/hhvm/blob/master/hphp/doc/apc_sample_serializer.php
 *
 * Write .cpp files for building libapc_prime.so that can be loaded by an
 * HPHP-compiled server at startup time.
 */
class CachePrimingHphpSerializer extends CachePrimingSerializer {
  public function __construct($archive_name) {
    $this->archive_name = $archive_name;
  }

  public function getName() {
    return 'hphp';
  }

  public function serialize($data) {
    $tmp = 0;
    $out = "\n#define COMPRESS_PRIMING_DATA 1\n";
    $out .= "\n#include \"apc_prime.h\"\n\n";
    $out .= "namespace HPHP {\n";
    $out .= str_repeat('/', 79)."\n\n";
    $out .= "APC_BEGIN(".$this->getArchiveId().");\n";

    unset($data['dummy_key']);
    $this->serializeStrings($out, $data);
    $this->serializeInt64  ($out, $data);
    $this->serializeObjects($out, $data);
    $this->serializeChars  ($out, $data);
    $this->serializeThrifts($out, $data);
    $this->serializeOthers ($out, $data);

    if (false) {
      // disable const_fetch, but we may use the const property of archive
      // for other optimization
      $out .= "static const bool HPHP_ARCHIVE_CONST_FETCH = true;\n";
    } else {
      $out .= "static const bool HPHP_ARCHIVE_CONST_FETCH = false;\n";
    }

    $out .= "APC_END(".$this->getArchiveId().");\n\n";
    $out .= str_repeat('/', 79)."\n";
    $out .= "}\n";
    return $out;
  }

  private function getArchiveId() {
    // used for cpp variable name, it's not archive name
    return preg_replace('/[-:]/', '_', $this->archive_name);
  }

  private function s($s) {
    static $esc_chars = "\0\n\r\t\\\"?";
    $slen = strlen($s);
    $s = addcslashes($s, $esc_chars);
    return "\"$s\",S($slen)";
  }

  private function compressStrings($strs, &$lens, &$bytes) {
    if (count($strs) == 0) {
      $lens = "0, 0\n";
      $bytes = "";
      return;
    }
    $lens = "";
    $bytes = "";
    $i = 0;
    $buf = "";
    foreach ($strs as $str) {
      $lens .= strlen($str) . ',';
      $buf .= $str . "\0" ; // add \0 after each string
      if (++$i % 10 == 0) {
        $lens .= "\n";
      } else {
        $lens .= " ";
      }
    }

    $code = gzencode($buf, 9);

    // The first element of $lens is the number of strings, and the second
    // element is the length of the encoding.
    $lens = count($strs) . ', ' . strlen($code) . ",\n" . $lens;

    $code .= "\x00"; // end the compressed string with \0
    foreach (str_split($code, 2048) as $chunk) {
      $bytes .= "\"".addcslashes($chunk,"\0\n\r\t\\\"?")."\"\n";
    }
  }

  private function serializeChars(&$out, &$data) {
    $i = 0;
    $values = "static char char_values[] = {\n";
    $keys = array();
    $klen = $vlen = 0;
    foreach ($data as $k => $v) {
      if ($v === null || is_bool($v)) {
        $keys[] = $k;
        if ($v === null) {
          $values .= '2,';
        } else if ($v) {
          $values .= '1,';
        } else {
          $values .= '0,';
        }
        if (++$i % 10 == 0) {
          $values .= "\n";
        }
        unset($data[$k]);
        $klen += strlen($k);
        $vlen ++;
      }
    }
    $this->addKeyStat('char', $klen, $vlen);
    $values .= "\n};\n";

    $klens = $kbytes = "";
    $this->compressStrings($keys, $klens, $kbytes);

    $out .= "static int char_lens[] = {\n" . $klens . "\n};\n";
    $out .= "static const char char_keys[] = {\n" . $kbytes . "\n};\n";
    $out .= $values;
  }

  private function serializeInt64(&$out, &$data) {
    $i = 0;
    $values = "static int64 int_values[] = {\n";
    $keys = array();
    $klen = $vlen = 0;
    foreach ($data as $k => $v) {
      if (is_int($v)) {
        $keys[] = $k;
        if ($v >= (1 << 32)) {
          $values .= $v.'LL,';
        } else {
          $values .= $v.',';
        }
        if (++$i % 10 == 0) {
          $values .= "\n";
        }
        unset($data[$k]);
        $klen += strlen($k);
        $vlen += 8;
      }
    }
    $this->addKeyStat('int64', $klen, $vlen);
    $values .= "\n};\n";

    $klens = $kbytes = "";
    $this->compressStrings($keys, $klens, $kbytes);

    $out .= "static int int_lens[] = {\n" . $klens . "\n};\n";
    $out .= "static const char int_keys[] = {\n" . $kbytes . "\n};\n";
    $out .= $values;
  }

  private function serializeStrings(&$out, &$data) {
    $kvs = array();
    $klen = $vlen = 0;
    foreach ($data as $k => $v) {
      if (is_string($v)) {
        $kvs[] = $k;
        $kvs[] = $v;
        $klen += strlen($k);
        $vlen += strlen($v);
        unset($data[$k]);
      }
    }
    $this->addKeyStat('string', $klen, $vlen);

    $kvlens = $kvbytes = "";
    $this->compressStrings($kvs, $kvlens, $kvbytes);

    $out .= "static int string_lens[] = {\n" . $kvlens . "\n};\n";
    $out .= "static const char strings[] = {\n" . $kvbytes . "\n};\n";
  }

  private function serializeObjects(&$out, &$data) {
    $kvs = array();
    $klen = $vlen = 0;
    foreach ($data as $k => $v) {
      if (is_object($v)) {
        $sv = serialize($v);
        $kvs[] = $k;
        $kvs[] = $sv;
        $klen += strlen($k);
        $vlen += strlen($sv);
        unset($data[$k]);
      }
    }
    $this->addKeyStat('object', $klen, $vlen);

    $kvlens = $kvbytes = "";
    $this->compressStrings($kvs, $kvlens, $kvbytes);

    $out .= "static int object_lens[] = {\n" . $kvlens . "\n};\n";
    $out .= "static const char objects[] = {\n" . $kvbytes . "\n};\n";
  }

  private function serializeThrifts(&$out, &$data) {
    $kvs = array();
    $klen = $vlen = 0;
    foreach ($data as $k => $v) {
      $sv = fb_serialize($v);
      if ($sv) {
        $kvs[] = $k;
        $kvs[] = $sv;
        $klen += strlen($k);
        $vlen += strlen($sv);
        unset($data[$k]);
      }
    }
    $this->addKeyStat('thrift', $klen, $vlen);

    $kvlens = $kvbytes = "";
    $this->compressStrings($kvs, $kvlens, $kvbytes);

    $out .= "static int thrift_lens[] = {\n" . $kvlens . "\n};\n";
    $out .= "static const char thrifts[] = {\n" . $kvbytes . "\n};\n";
  }

  private function serializeOthers(&$out, &$data) {
    $kvs = array();
    $klen = $vlen = 0;
    foreach ($data as $k => $v) {
      $sv = serialize($v);
      $kvs[] = $k;
      $kvs[] = $sv;
      $klen += strlen($k);
      $vlen += strlen($sv);
    }
    $this->addKeyStat('other', $klen, $vlen);

    $kvlens = $kvbytes = "";
    $this->compressStrings($kvs, $kvlens, $kvbytes);

    $out .= "static int other_lens[] = {\n" . $kvlens . "\n};\n";
    $out .= "static const char others[] = {\n" . $kvbytes . "\n};\n";
  }

  /**
   * Track counts and uncompressed sizes of keys:
   *  - for each archive
   *  - for each archive, by object-type
   */

  private static $stats = array();

  private function addKeyStat($object_type, $key_len, $value_len) {
    incr(self::$stats[$this->archive_name][$object_type]['sizes'],
        'keys',
        $key_len);
    incr(self::$stats[$this->archive_name][$object_type]['sizes'],
        'values',
        $value_len);
    incr(self::$stats[$this->archive_name][$object_type], 'count');
  }

  private static function clearStats() {
    self::$stats = array();
  }

  public static function flushStats() {
    $stats = array();

    foreach (self::$stats as $archive_name => $object_types) {
      $matches = null;
      if (preg_match('/^(.*)_\d+$/', $archive_name, $matches) == 1) {
        $archive_name = $matches[1];
      }
      foreach ($object_types as $object_type => $sizes_and_counts) {
        foreach ($sizes_and_counts['sizes'] as $type => $size) {
          incr($stats[$archive_name][$object_type]['sizes'], $type, $size);
        }
        incr($stats[$archive_name][$object_type],
            'count',
            $sizes_and_counts['count']);
      }
    }

    $archive_sums = array();
    $archive_counts = array();
    $object_sums = array();
    $object_counts = array();
    foreach ($stats as $archive_name => $object_types) {
      foreach ($object_types as $object_type => $sizes_and_counts) {
        $sizes = $sizes_and_counts['sizes'];
        $count = $sizes_and_counts['count'];

        foreach ($sizes as $type => $size) { // type is key or value
          self::recordStat('stats',
              "$archive_name.$object_type.$type.bytes",
              $size);
        }

        $object_type_sum = array_sum($sizes);
        self::recordStat('stats',
            "$archive_name.$object_type.bytes",
            $object_type_sum);
        self::recordStat('stats',
            "$archive_name.$object_type.count",
            $count);
        incr($archive_sums, $archive_name, $object_type_sum);
        incr($object_sums, $object_type, $object_type_sum);
        incr($archive_counts, $archive_name, $count);
        incr($object_counts, $object_type, $count);
      }
    }
    foreach ($archive_sums as $archive_name => $sum) {
      self::recordStat('stats',
          $archive_name.'.bytes',
          $sum);
      self::recordStat('stats',
          $archive_name.'.count',
          $archive_counts[$archive_name]);
    }
    foreach ($object_sums as $object_type => $sum) {
      self::recordStat('stats.objects',
          $object_type.'.bytes',
          $sum);
      self::recordStat('stats.objects',
          $object_type.'.count',
          $object_counts[$object_type]);
    }

    self::clearStats();
  }

  private static function recordStat($entity, $key, $value) {
    echo "$entity::$key = $value\n";
  }
}

function main() {
  $archives = array();
  foreach (get_all_cache_data() as $name => $data) {
    $archives[] = $name;
    $serializer = CachePrimingSerializer::newSerializer($name);
    $data_serizalized = $serializer->serialize($data);
    file_put_contents($name.'.cpp', $data_serizalized);
  }

  // prepare main list
  $f = fopen('main.cpp', 'w');

  $includes = <<<ENDINCLUDES
  #include <string.h>
  #include <sys/utsname.h>

ENDINCLUDES;
  fprintf($f, $includes);

  fprintf($f, "\nnamespace HPHP {\n");
  foreach ($archives as $archive) {
    fprintf($f, "  extern void apc_load_%s();\n", $archive);
  }
  foreach ($archives as $archive) {
    fprintf($f, "  extern void const_load_%s();\n", $archive);
  }
  fprintf($f, "  extern void const_load();\n");
  fprintf($f, "}\n\n");

  for ($i = 0; $i < count($archives); $i++) {
    $archive = $archives[$i];
    fprintf($f, "extern \"C\" { void _apc_load_$i(); }\n");
    fprintf($f, "void _apc_load_$i() {\n");

    fprintf($f, "  HPHP::apc_load_%s();\n", $archive);

    fprintf($f, "}\n");

    fprintf($f, "extern \"C\" { void _const_load_$i(); }\n");
    fprintf($f, "void _const_load_$i() {\n");
    fprintf($f, "  HPHP::const_load_%s();\n", $archive);
    fprintf($f, "}\n");
  }
  fprintf($f, "extern \"C\" { int _apc_load_count() { return $i; } }\n");

  fprintf($f, "\n");
  fprintf($f, "extern \"C\" { void _apc_load_all(); }\n");
  fprintf($f, "void _apc_load_all() {\n");
  for ($i = 0; $i < count($archives); $i++) {
    fprintf($f, "  _apc_load_$i();\n", $i);
  }
  fprintf($f, "}\n");
  fprintf($f, "\n");

  fprintf($f, "extern \"C\" { void _hphp_const_load_all(); }\n");
  fprintf($f, "void _hphp_const_load_all() {\n");
    for ($i = 0; $i < count($archives); $i++) {
      fprintf($f, "  _const_load_$i();\n", $i);
    }
    fprintf($f, "  HPHP::const_load();\n");
  fprintf($f, "}\n");
  fprintf($f, "\n");
}

main();
