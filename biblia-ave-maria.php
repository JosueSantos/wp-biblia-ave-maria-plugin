<?php
/**
 * Plugin Name: Eco da Palavra – Bíblia Ave Maria
 * Plugin URI: https://github.com/JosueSantos/wp-biblia-ave-maria-plugin
 * Description: Shortcodes para exibir sumário e livros da Bíblia Ave Maria.
 * Version: 1.0.0
 * Author:      Josué Santos
 * Author URI:  https://josuesantos.github.io/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: biblia-ave-maria
 */

// Evitar acesso direto
if (!defined('ABSPATH')) exit;


/* -----------------------------------
 *  Dados da Bíblia (JSON inline)
 * ----------------------------------- */
function biblia_get_data() {
    static $cache = null;
    if ($cache !== null) return $cache;

    $json = file_get_contents(plugin_dir_path(__FILE__) . 'bibliaAveMaria.json');
    $cache = json_decode($json, true);
    return $cache;
}

/* -----------------------------------
 *  Shortcode: Sumário (Sidebar)
 * ----------------------------------- */
function shortcode_biblia_sumario() {
    $data = biblia_get_data();
    if (!$data) return "Erro ao carregar Bíblia.";

    $html  = "<div class='biblia-sumario'>";
    $html .= "<h3 class='biblia-sumario-title'>📖 Livros da Bíblia</h3>";

    foreach ($data as $testamento => $livros) {
        $html .= "<div class='biblia-sumario-bloco'>";
        $html .= "<h4 class='biblia-sumario-subtitle'>" . ucfirst($testamento) . "</h4>";
        $html .= "<ul class='biblia-sumario-list'>";

        foreach ($livros as $livro) {
            $nome = $livro['nome'];
            $slug = sanitize_title($nome);
            $url  = add_query_arg(array('livro' => $slug));
            $html .= "<li><a href='" . esc_url($url) . "' class='biblia-sumario-link'>{$nome}</a></li>";
        }

        $html .= "</ul></div>";
    }

    $html .= "</div>";
    return $html;
}
add_shortcode('biblia_sumario', 'shortcode_biblia_sumario');


/* -----------------------------------
 *  Shortcode: Livro + Capítulos + Versículos
 * ----------------------------------- */
function shortcode_biblia_livro($atts) {
    $atts = shortcode_atts(array(
        'nome' => ''
    ), $atts);

    $data = biblia_get_data();
    if (!$data) return "Erro ao carregar Bíblia.";

    $paramSlug = '';
    if (!empty($atts['nome'])) {
        $paramSlug = sanitize_title($atts['nome']);
    } elseif (isset($_GET['livro'])) {
        $paramSlug = sanitize_title(wp_unslash($_GET['livro']));
    }

    if (empty($paramSlug)) {
        return '<p class="eco-msg">Selecione um livro no sumário.</p>';
    }

    $capFiltro = isset($_GET['cap']) ? intval($_GET['cap']) : 0;

    $html = "<div class='eco-livro'>";
    $encontrado = false;

    foreach ($data as $testamento => $livros) {
        foreach ($livros as $livro) {
            $slugLivro = sanitize_title($livro['nome']);
            if ($slugLivro === $paramSlug) {
                $encontrado = true;

                $html .= "<h2 class='eco-livro-title'>{$livro['nome']}</h2>";

                // Navegação por capítulos
                if (!empty($livro['capitulos'])) {
                    $html .= "<nav class='eco-cap-nav'>";
                    foreach ($livro['capitulos'] as $c) {
                        $n = intval($c['capitulo']);
                        $url = add_query_arg(array('livro' => $paramSlug, 'cap' => $n));
                        $html .= "<a href='" . esc_url($url) . "' class='eco-cap-btn'>{$n}</a>";
                    }
                    $html .= "</nav>";
                }

                // Exibir capítulos
                foreach ($livro['capitulos'] as $cap) {
                    $numCap = intval($cap['capitulo']);
                    if ($capFiltro > 0 && $numCap !== $capFiltro) continue;

                    $html .= "<section class='eco-capitulo' id='cap-{$numCap}'>";
                    $html .= "<h3 class='eco-capitulo-title'>Capítulo {$numCap}</h3>";

                    if (!empty($cap['versiculos'])) {
                        foreach ($cap['versiculos'] as $verso) {
                            $n = intval($verso['versiculo']);
                            $texto = esc_html($verso['texto']);
                            $html .= "<p class='eco-versiculo'><span class='eco-num'>{$n}</span> {$texto}</p>";
                        }
                    }

                    $html .= "</section>";
                }

                break 2;
            }
        }
    }

    if (!$encontrado) {
        $html .= "<p class='eco-msg'>Livro não encontrado. Verifique o parâmetro <code>?livro=</code>.</p>";
    }

    $html .= "</div>";
    return $html;
}
add_shortcode('biblia_livro', 'shortcode_biblia_livro');


/* -----------------------------------
 *  CSS moderno e responsivo
 * ----------------------------------- */
function biblia_custom_styles() {
    echo "
    <style>
    /* --- SUMÁRIO --- */
    .biblia-sumario {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        font-family: 'Segoe UI', Roboto, sans-serif;
        margin-bottom: 2rem;
    }
    .biblia-sumario-title {
        font-size: 1.4rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: #1a365d;
        text-align: center;
    }
    .biblia-sumario-subtitle {
        font-size: 1.1rem;
        font-weight: 500;
        margin: 10px 0;
        color: #2b6cb0;
        border-left: 4px solid #2b6cb0;
        padding-left: 8px;
    }
    .biblia-sumario-list {
        list-style: none !important;
        padding-left: 0 !important;
        margin: 0 !important;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }
    .biblia-sumario-link {
        display: block;
        padding: 6px 10px;
        border-radius: 6px;
        text-decoration: none;
        background: #f7fafc;
        color: #2d3748;
        font-size: 0.95rem;
        transition: all 0.2s ease-in-out;
    }
    .biblia-sumario-link:hover {
        background: #2b6cb0;
        color: #fff;
    }

    /* --- LIVRO --- */
    .eco-livro {
        max-width: 800px;
        margin: 1.5rem auto;
        padding: 1rem;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    }
    .eco-livro-title {
        text-align: center;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #0f172a;
    }

    /* Navegação de capítulos */
    .eco-cap-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
        margin-bottom: 1.5rem;
    }
    .eco-cap-btn {
        padding: 0.4rem 0.7rem;
        border-radius: 8px;
        background: #e0f2fe;
        color: #0369a1;
        text-decoration: none;
        font-size: 0.9rem;
        transition: 0.2s;
    }
    .eco-cap-btn:hover {
        background: #bae6fd;
        color: #075985;
    }

    /* Capítulo */
    .eco-capitulo {
        margin-bottom: 2rem;
    }
    .eco-capitulo-title {
        font-size: 1.3rem;
        font-weight: 600;
        background: #f1f5f9;
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        color: #334155;
        margin-bottom: 0.8rem;
    }

    /* Versículos */
    .eco-versiculo {
        margin: 0.3rem 0;
        line-height: 1.6;
        font-size: 1rem;
        color: #1e293b;
    }
    .eco-num {
        font-weight: 700;
        color: #64748b;
        margin-right: 0.4rem;
        font-size: 0.85rem;
    }

    /* Mensagens */
    .eco-msg {
        text-align: center;
        padding: 1rem;
        color: #ef4444;
    }

    /* Responsividade */
    @media (max-width: 600px) {
        .eco-livro { padding: 0.8rem; }
        .eco-livro-title { font-size: 1.5rem; }
        .eco-cap-btn { font-size: 0.8rem; padding: 0.3rem 0.6rem; }
        .biblia-sumario-list { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
    }
    </style>
    ";
}
add_action('wp_head', 'biblia_custom_styles');