<?php

# converter um array para um objeto
# @param array
function oo($array)
{
    return json_decode(json_encode($array),FALSE);
}

# verifica se uma string esta em formato utf8
function is_utf8($string)
{
    return preg_match('!!u', $string) ? true : false;
}

if (!function_exists('strtoupperr'))
{
    function strtoupperr($in)
    {
        if(!(is_string($in)||is_array($in))) return $in;
        if(is_string($in))
            return strtoupper($in);
        if(is_array($in)){
            return array_map('strtoupper',$in);
        }
    }
}

if(!function_exists('strtolowerr')):
    function strtolowerr($in){
        if(!is_string($in)||!is_array($in)) return $in;
        if(is_string($in))
            return strtolower($in);
        if(is_array($in)){
            array_map('strtolower',$in);
        }
    }
endif;


if ( ! function_exists('redirect'))
{
    /**
     * Redireciona para uma nova URL, em suma
     * chama um novo Controller e um Metodo
     *
     * @param  string $destination - ControllerClass/Method
     * @return void
     */
    function redirect($destination = '', $method = 'location', $http_response_code = 302)
    {
        if (strpos($destination, "http") === false) {
            $destination = \Vita\Vita::getInstance()->getConfig()->get('url') . $destination;
        }

        switch($method) {
            case 'refresh':
                header("Refresh:0;url=" . $destination);
            break;

            default :
                header("Location: " . $destination, TRUE, $http_response_code);
            break;
        }
        exit(0);
    }
}


if (!function_exists('uri'))
{
    /**
     * Obtém um segmento da URL ou toda ela
     *
     * @param  int|null - se null retorna toda URL se numero retorna segmento
     * @return string
     */
    function uri($segment = null)
    {
        # obtendo algo como : http://nomedosite.com/arquivo_solicitado
        $uri = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        # se foi solicitado um segmento da URI...
        if (isset($segment) && is_numeric($segment)) {
            $newUri = explode( "/", "$uri" );
            return isset($newUri[$segment+1]) ? $newUri[$segment+1] : '';
        }

        # retorna URI completa
        return trim($uri);
    }
}


/**
 * É inteiro? Verifica se um valor String trata-se de um valor inteiro
 */
function __eint($s){return filter_var($s, FILTER_VALIDATE_INT) !== false;}
function __enum($s){return eint($s);}


/**
*
* Quando uma traducao é solicitada atraves do TWIG o mesmo
* chama a função abaixo e solicita a tradução de um dado
* trecho de texto.
* Ex, no frontend: {{ 'texto a ser traduzido' | vtrans }}
* vtrans - é um alias para a função "vita_twig_translate_filter"
*
* Esta funcao, verifica se o texto se encontra dentro do
* arquivo/catalogo de traducoes que por padrao se localiza em
* config/locale/, caso encontre, retorna a informacao traduzida,
* caso contrario a mesma string de entrada é retornada para a
* funcao que chamou.
*
* @param  string $val - texto a traduzir
* @return string      - string traduzida
*/
function vita_twig_translate_filter( $val = null)
{
    # esta ativado configuracao de traducao ?
    if (!Vita\Vita::getInstance()->getConfig()->get('twig_localization_enabled')) {
        return $val;
    }

    # nosso parametro e' valido?
    if(is_null($val)||(!is_string($val))||empty($val)) return $val;


    # deixamos em lowercase por ser a parte a comparar
    $val = mb_strtolower(trim($val));

    # verifica se temos um catalogo carregado na memoria
    # isso evita que o sistema fique solicitando abertura e
    # fechamento de resource a todo momento que for traduzir
    # uma unica palavra.
    $catalogo = vita()->vita_translate_catalogo_arr;

    # se ainda nao temos um catalogo, carrega
    if(null == $catalogo):

        # arquivo alvo
        # tenta primeiro carregar arquivo da app que solicitou o vita
        $__locale_file = vita()->config->app_folder.vita()->config->twig_localization_locale_path.vita()->config->twig_localization_locale.'.yml';
        if(!file_exists($__locale_file)):
            $__locale_file = vita()->config->vita_path.vita()->config->twig_localization_locale_path.vita()->config->twig_localization_locale.'.yml';
            if(!file_exists($__locale_file))
                throw new Exception("Catalogo de traduções não foi encontrado em: '$__locale_file'", 1);
        endif;

        if (($handle = fopen($__locale_file, "r")))
        {
            $catalogo = array();
            while (($line = fgets($handle)) !== false):
                if(false === strpos($line, ":")) continue;
                list($text,$traducao) = explode(":", $line);
                $text = mb_strtolower(trim(str_replace(array('"',"'"), "", $text)));
                $traducao = trim(str_replace(array('"',"'"), "", $traducao));
                $catalogo[] = array('text'=>$text, 'traducao' => $traducao);
            endwhile;
            fclose($handle);
            vita()->vita_translate_catalogo_arr = $catalogo;

        }else throw new Exception("Não foi possível abrir o catalogo de traduções: '$__locale_file'", 1);

    endif;

    # percorre catalogo de traducoes a procura do texto solicitado
    foreach ($catalogo as $item)
        if($item['text'] == $val)
            return $item['traducao'];
    return $val;
}
