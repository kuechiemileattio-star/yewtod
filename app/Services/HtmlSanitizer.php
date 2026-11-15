<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Nettoyage du contenu riche (cahier § 3.2, § 8.4) avec HTML Purifier :
 * liste blanche stricte de balises, liens http/https/mailto uniquement,
 * aucun attribut d'événement, aucun style, aucune image externe (pas de
 * pixel de suivi). Appliqué AVANT l'enregistrement en base.
 */
class HtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return trim($this->purifier()->purify($html));
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier !== null) {
            return $this->purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $cache = storage_path('framework/purifier');
        if (! is_dir($cache)) {
            @mkdir($cache, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cache);
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,a[href|title],ul,ol,li,blockquote,h3,h4,code,pre,sup,sub,hr');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.TargetNoopener', true);
        $config->set('HTML.TargetNoreferrer', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Core.Encoding', 'UTF-8');

        return $this->purifier = new HTMLPurifier($config);
    }
}
