import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Fonction qui s'exécute quand l'élément apparaît sur la page
    connect() {
        this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
    }
}
