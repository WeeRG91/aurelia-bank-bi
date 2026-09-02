import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

if (csrfToken !== undefined) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

export { axios };
