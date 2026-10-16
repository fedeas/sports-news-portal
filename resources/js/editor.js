import Trix from 'trix';
import 'trix/dist/trix.css';
import '../css/editor.css';

// The article title is the page's only <h1>, so headings inside the body start at <h2>.
Trix.config.blockAttributes.heading1.tagName = 'h2';

// Images go through the media pipeline, never pasted or dropped into the body.
document.addEventListener('trix-file-accept', (event) => event.preventDefault());
