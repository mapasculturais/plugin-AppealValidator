<?php 
use MapasCulturais\i;

$slo_slug = $slo_instance->config['slug'];

$app = MapasCulturais\App::i();

$slug = $plugin->getSlug();
$name = $plugin->getName();

$route = MapasCulturais\App::i()->createUrl($slug, 'export', ['opportunity' => $opportunity, 'slo_slug' => $slo_slug]);    
?>

<a href="<?=$route?>" class="btn btn-default download btn-export-cancel"><?php i::esc_attr_e('Baixar template') ?></a>