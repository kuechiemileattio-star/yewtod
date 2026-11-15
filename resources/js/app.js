/*
 * Point d'entrée JavaScript (compilé par Vite, servi avec le nonce CSP).
 *
 * Livewire + Alpine sont chargés dans leur build « CSP » : aucune expression
 * n'est évaluée avec eval()/new Function(). Conséquence : toute logique un
 * peu riche vit ici, dans des composants Alpine.data(), et les gabarits
 * Blade n'utilisent que des expressions simples (propriétés, appels).
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.csp.esm';
import { registerComponents } from './components';

registerComponents(Alpine);
Livewire.start();
