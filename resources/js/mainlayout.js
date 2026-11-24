import 'bootstrap';
import { Modal } from 'bootstrap';

const deletionModal = document.querySelector('[data-auto-show="true"]');

if (deletionModal) {
    Modal.getOrCreateInstance(deletionModal).show();
}
