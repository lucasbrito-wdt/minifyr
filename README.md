# Minifyr
Minifica e agrupa scripts CSS ou JS. 

Se você estiver interessado em economizar largura de banda, reduza o tempo de carregamento e acelerar seu site ou aplicativo da web, então, o Minifier é bom para você.

## Como usar

Fork (ou baixar) este projeto;

Copie e cole o arquivo "minifyr.php" em qualquer pasta do seu projeto. Esta é a classe.

Por exemplo:

```bash
/ (Diretório raiz do projeto)
/ classes/minifyr.php
/ ...
```

Agora, crie o script que vai usá-lo para minificar os recursos que você precisa.

Como exemplo, crie um arquivo chamado _min.php_ na pasta raiz do seu projeto, como abaixo:

```bash
/ (Diretório raiz do projeto)
/ classes/minifyr.php
/ min.php
/ ...
```
Então, você pode usar o seguinte código para que isso aconteça:

```php
require_once('src/minifyr.php');

// Obter configurações e arquivos para minificar
// As opções são:
//   f			- Requeridos. Lista de arquivos separados por vírgulas ou vírgulas
//	 screen	- Opcional. Vazio. Força o download do arquivo minificado.
// 	 debug	- Opcional. Vazio. Quando administrado, ignore a minificação.
// 
// @use http://domain.tld/min.php?f=assets/my.css[&screen[&debug]]

$debug  = isset( $_GET[ 'debug' ] ) ? TRUE : FALSE;
$screen = isset( $_GET[ 'screen' ] ) ? TRUE : FALSE;
$files  = isset( $_GET[ 'f' ] ) ? $_GET[ 'f' ] : NULL;

$m = new RT\Minifyr($debug, $screen);
$m->files( explode(',', $files) )
  ->compression(true)   // Pode ser true/false. Habilite a compressão gzip 
  ->cache(true)         // Pode ser true/false. Habilita o cabeçalho para o cache 
  ->uglify(true)        // Pode ser true/false. uglify js codes
  ->expires('...')      // Uma string que define a data de validade
  ->charset('...')      // O charset. O padrão é utf-8
  ->files([])           // Uma série de strings contendo caminhos de arquivos
  ->file('...')         // Quando apenas um arquivo, uma string com caminho de arquivo 
  ->render(false);      // Torna a saída. 
                        // Se um true booleano for dado, retorna a saída como seqüência de caracteres.
```

Agora, tudo o que você precisa fazer é chamá-lo em seu arquivo HTML:

```html
<link type="text/css" media="all" href="min.php?f=path/to/css/file.css" />
```

É isso aí. Fácil e simples. Muito fácil! :)

## Opções

Estas são as opções que você pode passar:

|   Opção   | Amostra |  Descrição  |
| --------- | ------- | ----------- |
| f      | `min.php?f=file-path.css` | É o arquivo a ser minificado. * |
| screen | `min.php?screen&f=...`    | É a maneira de renderizar o conteúdo no navegador e devolvê-lo como um arquivo. |
| debug  | `min.php?debug&f=...`     | É uma maneira de não minificar o conteúdo. Isso ajuda você a depurar seus códigos. |

### Utilização avançada para:

#### Opção `f` : `string`


Você também pode passar uma lista de arquivos. Neste caso, todos os arquivos serão carregados e serão retornados minificados como um arquivo exclusivo. Esta técnica é interessante para reduzir o número de chamadas que você faz para o seu servidor.
Para passar uma lista de arquivos, você deve dar nomes de arquivos separados por vírgulas (,):

E.g:
```
min.php?f=assets/css/my-css-file-1.css,assets/css/my-css-file-2.css,...
```

Você também pode carregar recursos externos.
Para fazer isso, basta passar o arquivo com um prefixo: `external|`.

E.g:
```
min.php?f=external|code.jquery.com/jquery-2.1.1.min.js[, ...]
```

## Mudanças

1.6 Adicionado suporte para arquivos externos. Impedir minificação dupla em arquivos já minificados.

2.0 Refatorado de um "modo de script" para um "modo de classe". Novos recursos adicionados.
