<?php

// Action qui permet de charger des scripts dans notre thème
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');
function theme_enqueue_styles()
{
    // Chargement du style.css du thème parent Twenty Twenty
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    // Chargement du css/theme.css pour nos personnalisations
    wp_enqueue_style('theme-style', get_stylesheet_directory_uri() . '/css/theme.css', array(), filemtime(get_stylesheet_directory() . '/css/theme.css')); //le 4eme paramètre (filemtime) permet de gérer la version du fichier, et donc d’éviter tout problème de cache
    // Chargement du /css/widgets/image-titre-widget.css pour notre widget image titre
    wp_enqueue_style('image-titre-widget', get_stylesheet_directory_uri() . '/css/widgets/image-titre-widget.css', array(), filemtime(get_stylesheet_directory() . '/css/widgets/image-titre-widget.css'));

    // Chargement du /css/widgets/bloc-lien-image-widget.css pour notre widget bloc lien image
    wp_enqueue_style('bloc-lien-image-widget', get_stylesheet_directory_uri() . '/css/widgets/bloc-lien-image-widget.css', array(), filemtime(get_stylesheet_directory() . '/css/widgets/bloc-lien-image-widget.css'));
    // Chargement du /css/shortcodes/banniere-titre.css pour notre shortcode banniere titre
    wp_enqueue_style('banniere-titre-shortcode', get_stylesheet_directory_uri() . '/css/shortcodes/banniere-titre.css', array(), filemtime(get_stylesheet_directory() . '/css/shortcodes/banniere-titre.css'));
}


/* CHARGEMENT DES WIDGETS */


// On créer une class Widget Image_Titre_Widget dans un fichier à part pour pas surcharger le functions.php
require_once(__DIR__ . '/widgets/ImageTitreWidget.php');
require_once(__DIR__ . '/widgets/BlocLienImageWidget.php');


function register_widgets()
{
    //On enregistre le widget avec la class Image_Titre_Widget
    register_widget('Image_Titre_Widget');
    register_widget('Bloc_Lien_Image_Widget');
}
//On demander à wordpress de charger des widget selon la fonction register_widgets()
add_action('widgets_init', 'register_widgets');


/* SHORTCODES */


// Je dis à wordpress que j'ajoute un shortcode 'banniere-titre'
add_shortcode('banniere-titre', 'banniere_titre_func');

// Je génère le html retourné par mon shortcode
function banniere_titre_func($atts)
{
    //Je récupère les attributs mis sur le shortcode
    $atts = shortcode_atts(array(
        'src' => '',
        'titre' => 'Titre'
    ), $atts, 'banniere-titre');

    //Je commence à récupéré le flux d'information
    ob_start();

    if ($atts['src'] != "") {
?>

        <div class="banniere-titre" style="background-image: url(<?= $atts['src'] ?>)">
            <h2 class="titre"><?= $atts['titre'] ?></h2>
        </div>

    <?php
    }

    //J'arrête de récupérer le flux d'information et le stock dans la fonction $output
    $output = ob_get_contents();
    ob_end_clean(); //Je nettoie le flux pour une bonne utilisation

    return $output; //pour générer un shortcode il faut nécessairement retourner le html du shortcode sous forme de texte
}



/* HOOK FILTERS */




//on prend en compte ici la page de chaque outil
function the_title_filter($title)
{

    if (is_single() && in_category('outils') && in_the_loop()) { //is_single() : Vérifie que la page en cours est un article individuel, et in_the_loop() : S'assure que le code est exécuté dans la boucle WordPress (généralement le corps de page)
        return 'Outil : ' . $title;
    }

    return $title;
}
add_filter('the_title', 'the_title_filter');


//on prend en compte ici la page d'archives
add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        $title = "Liste des " . strtolower(single_cat_title('', false));
    }

    return $title;
});

//on personnalise le titre d’un article et de la page d’archive
function the_category_filter($categories)
{
    return str_replace("Outils", "Tous les outils", $categories);
}

add_filter('the_category', 'the_category_filter');


//on ajoute le titre "Description" a la zone de contenu de la page de détail d’un outil
function the_content_filter($content)
{
    if (is_single() && in_category('outils')) {
        return '<hr><h2>Description</h2>' . $content;
    }
    return $content;
}

add_filter('the_content', 'the_content_filter');


//on ajoute ici un bouton pour accéder au détail d’un outil
function the_excerpt_filter($content)
{
    if (is_archive()) {
        return $content . "<div class='more-excerpt'><a href='" . get_the_permalink() . "'>En savoir plus sur l'outil</a></div>"; //get_the_permalink() utilisé en lien href permet de générer le lien WordPress de la page de détail de l’article courant dans lequel on affiche l’excerpt.
    }
    return $content;
}

add_filter('the_excerpt', 'the_excerpt_filter');


function paginate_links_filter($r)
{
    //error_log("$r"); //pour afficher dans le fichier debug.log (situé dans wp-contents) une piste pour l'erreur ci-bas
    return "Pages :" . $r;
    //return "Pages :" + $r; //l'erreur est le "+" utilisé alors qu'il s'agit de concatenation de chaînes de carac
}

add_filter('paginate_links_output', 'paginate_links_filter');





/* HOOKS ACTIONS */




//on affiche une bannière a la fin de la page archives
function loop_end_action()
{
    if (is_archive()):
    ?>
        <p class="after-loop">
            <?php
            echo do_shortcode('[banniere-titre src="http://localhost/bricotips/wp-content/uploads/2025/03/banniere-image.webp" titre="BricoTips"]');
            ?>
        </p>
    <?php
    endif;
}

add_action('loop_end', 'loop_end_action');



//on affiche un texte d'intro au début de la page archives
$shown = false; //cette variables est definie en dehors des fonctions

function bricotips_intro_section_action()
{
    global $shown; //donc pour la manipuler cette variable, elle doit etre declaree comme globale
    if (is_archive() && !$shown):
    ?>
        <p class="intro">Vous trouverez dans cette page la liste de tous les outils que nous avons référencée pour le
            moment. La liste n'est pas exhaustive, mais s'enrichira au fur et à mesure.</p>
<?php
        $shown = true; //lors des appels suivants, $shown est déjà true, donc le texte n'est plus affiché
    endif;
}

add_action('bricotips_intro_section', 'bricotips_intro_section_action'); //l'erreur etait le ";" omis sur cette ligne

// passer en mode maintenance si l'utilisateur n'est pas un administrateur du site
function maintenace_mode()
{
    if (!current_user_can('administrator')) {
        wp_die('Maintenance.');
    }
}
add_action('get_header', 'maintenace_mode');
