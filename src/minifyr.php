<?php

  namespace LuquinhasBrito\Minify;

  class Minifyr {
    private $_version = '2.0.0';
    private $_path = '';
    private $_type = '';
    private $_extension = '';
    private $_base_url = '';
    private $_expires = '';
    private $_charset = 'utf-8';
    private $_allowed_files = array('css', 'js');
    private $_content_types = array('css' => 'text/css', 'js' => 'text/javascript');
    private $_minified = array();
    private $_files = array();
    private $_debug = false;
    private $_screen = false;
    private $_allow_compression = true;
    private $_allow_cache = true;
    private $_uglify = false;

    function __construct($debug = false, $screen = false) {
      $this->_debug = $debug;
      $this->_screen = $screen;

      // Recuperar o URL atual no qual o arquivo está sendo chamado
      $this->_base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . preg_replace('/\/\//', '/', "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}");
      $this->_base_url = pathinfo(substr($this->_base_url, 0, strpos($this->_base_url, '?')));

      $this->_expires = gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT';
    }

    /**
     * <h1>Compressão</h1>
     * @param type $flag
     * @return $this
    */    
    public function compression($flag = true) {
      $this->_allow_compression = $flag;
      return $this;
    }

    /**
     * <h1>Cache</h1>
     * @param type $flag
     * @return $this
    */    
    public function cache($flag = true) {
      $this->_allow_cache = $flag;
      return $this;
    }
    
    /**
     * <h1>Uglify</h1>
     * @param type $flag
     * @return $this
    */
    public function uglify($flag = false) {
      $this->_uglify = $flag;
      return $this;
    }
    
    /**
     * <h1>Expires</h1>
     * @param type $time
     * @return $this
    */
    public function expires($time = '') {
      if (!empty($time)) {
        $this->_expires = $time;
      }

      return $this;
    }

    /**
     * <h1>Charset</h1>
     * @param type $charset
     * @return $this
    */
    public function charset($charset = '') {
      if (!empty($charset)) {
        $this->_charset = $charset;
      }

      return $this;
    }

    /**
     * <h1>Files</h1>
     * @param type $files
     * @return $this
    */
    public function files($files = array()) {
      if (is_array($files)) {
        $this->_files = array_merge($this->_files, $files);
      }

      $this->_files = array_filter($this->_files, function ($el) {
        return !empty($el) && is_string($el);
      });

      return $this;
    }
    
    /**
     * <h1>File</h1>
     * @param type $file
     * @return $this
    */
    public function file($file = '') {
      if (!empty($file)) {
        $this->_files[] = $file;
      }

      return $this;
    }
    
    /**
     * <h1>Renderizar</h1>
     * @param type $return
     * @return boolean
    */
    public function render($return = false) {

      $output = $this->run();

      // Quer devolvê-lo como string?
      if ($return === true) {
        return $output;
      }

      if ($this->_allow_compression === true) {
        // Habilitar a compressão gzip
        ob_start("ob_gzhandler");
      }

      if ($this->_allow_cache === true) {
        // Permitir cache
        header('Cache-Control: public');
      }

      // Expira em um dia
      header('Expires: ' . $this->_expires);

      // Definir o tipo de conteúdo e o charset direito ...
      header("Content-type: {$this->_type}; charset={$this->_charset}");

      if ($this->_screen === false) {
        // Forçar o arquivo como 'arquivo para download
        header("Content-disposition: attachment; filename=minified.{$this->_extension}");
      }

      $header = '/** ' . PHP_EOL;
      $header .= " * Minifyr #v.{$this->_version} " . PHP_EOL;
      $header .= ' * Licensed under MIT license:' . PHP_EOL;
      $header .= ' * http://www.opensource.org/licenses/mit-license.php' . PHP_EOL;
      $header .= ' * @author Web Design Technologies (webdesigntechnologies@outlook.com.br)' . PHP_EOL;
      $header .= ' * @see https://github.com/rogeriotaques/minifyr' . PHP_EOL;
      $header .= ' */' . PHP_EOL . PHP_EOL;

      print "{$header}{$output}";
      return true;
    }

    /**
     * <h1>Execução</h1>
     * @return type
    */
    private function run() {
      $output = array();

      if (count($this->_files) === 0) {
        trigger_error('Minifyr: Não há arquivos a serem minificados!', E_WARNING);
        exit;
      }

      foreach ($this->_files as $file) {
        // Permitir arquivos externos
        // Sempre que é um arquivo externo, carregue-o de sua origem
        $external = preg_match('/^external\|/', $file) ? TRUE : FALSE;
        if ($external)
          $file = preg_replace('/^external\|/', 'http://', $file);

        $inf = pathinfo($file);
        $is_minified = strpos($inf['basename'], '.min') !== false;

        // Ignore o arquivo se ele for inválido ou já foi minificado.
        // Os arquivos considerados inválidos são: extensões não permitidas ou com caminho apontando para pastas pai (../)
        if (!$file || !in_array($inf['extension'], $this->_allowed_files) || strpos($inf['dirname'], '../') !== false || in_array($inf['basename'], $this->_minified)) {
          $output[] = "/* File: {$file} was ignored. It's invalid or was minified already. */" . PHP_EOL . PHP_EOL;
          continue;
        }

        // Decida o tipo de conteúdo de acordo com o primeiro tipo de arquivo.
        if (empty($this->_type)) {
          $this->_type = $this->_content_types[$inf['extension']];
          $this->_extension = $inf['extension'];
        }

        // Se a extensão não for a mesma dos primeiros arquivos minificados, então ignore.
        if ($inf['extension'] != $this->_extension) {
          $output[] = "/* File: {$file} was ignored. File's extension doesn't match file type pattern. */" . PHP_EOL . PHP_EOL;
          continue;
        }

        // Evitar a dupla mineração ...
        $this->_minified[] = $file;
        $minified_content = '';

        if (!$is_minified) {
          $minified_content = $this->_debug === false ? $this->minify($file) : @file_get_contents($file);
          $minified_content = strpos($this->_type, 'css') !== false ? $this->fix_path($minified_content, $this->_base_url['dirname'] . '/' . $inf['dirname']) : $minified_content;
        } else {
          $minified_content = @file_get_contents($file);
        }

        $output[] = "/* File: {$file} */" . PHP_EOL . PHP_EOL;

        if ($this->_uglify === true && strtolower($this->_type) === 'text/javascript') {
          $minified_content = $this->do_uglify($minified_content);
        }

        $output[] = $minified_content . PHP_EOL . PHP_EOL;
      }

      return implode('', $output);
    }

    /**
     * <h1>Minify</h1>
     * @param type $path
     * @return type
     */
    private function minify($path = '') {
      // Obter conteúdo de arquivo
      $content = @file_get_contents($path);

      // Remova todos os blocos de comentários
      $content = preg_replace('#/\*.*?\*/#s', '', $content);

      // Remova todas as linhas de comentários
      $content = preg_replace('#//(.*)$#m', '', $content);

      // Remover todos os espaços em branco
      $content = preg_replace('#\s+#', ' ', $content);

      // Remova espaços desnecessários (antes de | depois) alguns sinais ...
      $content = str_replace(array('{ ', ' {'), '{', $content);
      $content = str_replace(array('} ', ' }'), '}', $content);
      $content = str_replace(array('; ', ' ;'), ';', $content);
      $content = str_replace(array(': ', ' :'), ':', $content);
      $content = str_replace(array(', ', ' ,'), ',', $content);
      $content = str_replace(array('|| ', ' ||'), '||', $content);
      $content = str_replace(array('! ', ' !'), '!', $content);

      // Execute diferentes maneiras de remover alguns desnecessários 
      // Espaços (antes de | depois) alguns sinais ...
      switch ($this->_type) {
        case 'css':
          $content = str_replace(array('( ', ' ('), '(', $content);
          $content = str_replace(array(' )'), ')', $content);
          $content = str_replace(array('= ', ' ='), '=', $content);
        break;
        case 'js':
          $content = str_replace(array('( ', ' ('), '(', $content);
          $content = str_replace(array(' )', ') '), ')', $content);
          $content = str_replace(array('= ', ' ='), '=', $content);
          $content = str_replace(array('+ ', ' +'), '+', $content);
          $content = str_replace(array('- ', ' -'), '-', $content);
          $content = str_replace(array('* ', ' *'), '*', $content);
          $content = str_replace(array('/ ', ' /'), '/', $content);
        break;
      }
      return trim($content);
    }

    /**
     * Corrija todos os caminhos relativos que são fornecidos no conteúdo do arquivo
     * É útil quando os designers fornecem caminhos relativos para imagens ou arquivos css adicionais em determinado arquivo css.
     * Evite a referência frouxa para imagens configuradas no arquivo css.
    */
    
    /**
     * <h1>Corrigir caminho</h1>
     * <p>
     * Corrija todos os caminhos relativos que são fornecidos no conteúdo do arquivo
     * É útil quando os designers fornecem caminhos relativos para imagens ou arquivos css adicionais em determinado arquivo css.
     * Evite a referência frouxa para imagens configuradas no arquivo css.
     * </p>
     * @param type $content
     * @param type $path
     * @return type
    */
    private function fix_path($content = '', $path = '') {
      // Primeiro caminho de reparo para essas referências sem ../
      $content = preg_replace('/(url\()(\'|\"){0,1}([a-zA-Z0-9\-\_\.]+)(\.png|\.jpg|\.jpge|\.gif|\.bmp|\.PNG|\.JPG|\.JPEG|\.GIF|\.BMP])/', "$1$2{$path}/$3$4", $content);

      // Então, remova o último diretório do caminho dado para garantir ../ será substituído corretamente.
      $path = substr($path, 0, strrpos($path, '/'));
      $content = preg_replace('/(\.\.\/)/', "{$path}/$2", $content);
      return $content;
    }
    
    /**
     * <h1>Uglify</h1>
     * <p>
     * Obter um código JS bruto e retorna uglified.
     * Que usa o método Google Closure.
     * </p>
     * @param type $raw_code
     * @return type
     */
    private function do_uglify($raw_code) {
      if (!function_exists('curl_init')) {
        trigger_error('Minifyr: Impossível uglify seu código, esta instância PHP não tem CURL.');
        return $raw_code;
      }

      $curl = curl_init();
      $headers = array('application/x-www-form-urlencoded');
      $body = array('js_code' => $raw_code);

      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_FAILONERROR, true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($curl, CURLOPT_VERBOSE, true);
      curl_setopt($curl, CURLINFO_HEADER_OUT, true);
      curl_setopt($curl, CURLOPT_URL, 'https://marijnhaverbeke.nl/uglifyjs');
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));

      $uglified = curl_exec($curl);
      $info = curl_getinfo($curl);

      if ($uglified === false) {
        $uglified = $raw_code;
      }
      return $uglified;
    }
  }