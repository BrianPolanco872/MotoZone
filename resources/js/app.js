import './admin/product-form';
import './pos/pos';
import './home-editor';
import './carousel';
import './home-categories';
import './home-parallax';
import './ticket-settings';

document.querySelectorAll('[data-auto-submit]').forEach((element) => {
    element.addEventListener('change', () => element.form?.requestSubmit());
});

document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-dismissable][open]').forEach((details) => {
        if (!details.contains(event.target)) {
            details.removeAttribute('open');
        }
    });
});
