(() => {
  'use strict';
  const source = document.getElementById('body');
  const preview = document.getElementById('markdown-preview');
  const empty = document.getElementById('preview-empty');
  if (!source || !preview) return;

  function appendInline(parent, value) {
    const pattern = /(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`|[[^]]+]([^)]+))/g;
    let last = 0;
    for (const match of value.matchAll(pattern)) {
      if (match.index > last) parent.append(document.createTextNode(value.slice(last, match.index)));
      const token = match[0];
      let node;
      if (token.startsWith('**')) {
        node = document.createElement('strong'); node.textContent = token.slice(2, -2);
      } else if (token.startsWith('*')) {
        node = document.createElement('em'); node.textContent = token.slice(1, -1);
      } else if (token.charAt(0) === String.fromCharCode(96)) {
        node = document.createElement('code'); node.textContent = token.slice(1, -1);
      } else {
        const link = token.match(/^[([^]]+)](([^)]+))$/);
        const href = link ? link[2] : '';
        if (link && (/^https?:///i.test(href) || /^mailto:/i.test(href) || (href.startsWith('/') && !href.startsWith('//')))) {
          node = document.createElement('a'); node.textContent = link[1]; node.setAttribute('href', href);
          node.setAttribute('rel', 'nofollow noopener noreferrer');
        } else {
          node = document.createTextNode(link ? link[1] : token);
        }
      }
      parent.append(node);
      last = match.index + token.length;
    }
    if (last < value.length) parent.append(document.createTextNode(value.slice(last)));
  }

  function render() {
    const text = source.value || '';
    preview.replaceChildren();
    if (!text.trim()) {
      if (empty) empty.hidden = false;
      return;
    }
    if (empty) empty.hidden = true;
    const lines = text.split(/
||
/);
    const fence = String.fromCharCode(96).repeat(3);
    let paragraph = [];
    let list = null;
    let inCode = false;
    let code = [];
    const flushParagraph = () => {
      if (!paragraph.length) return;
      const p = document.createElement('p');
      paragraph.forEach((line, i) => { if (i) p.append(document.createElement('br')); appendInline(p, line); });
      preview.append(p); paragraph = [];
    };
    const flushList = () => { list = null; };
    for (const line of lines) {
      if (line.trimStart().startsWith(fence)) {
        flushParagraph(); flushList();
        if (inCode) {
          const pre = document.createElement('pre'); const codeNode = document.createElement('code');
          codeNode.textContent = code.join('
'); pre.append(codeNode); preview.append(pre);
          code = []; inCode = false;
        } else inCode = true;
        continue;
      }
      if (inCode) { code.push(line); continue; }
      if (!line.trim()) { flushParagraph(); flushList(); continue; }
      const heading = line.match(/^(#{1,6})s+(.+)$/);
      if (heading) {
        flushParagraph(); flushList();
        const h = document.createElement('h' + heading[1].length); appendInline(h, heading[2]); preview.append(h); continue;
      }
      const quote = line.match(/^s*>s?(.*)$/);
      if (quote) {
        flushParagraph(); flushList();
        const blockquote = document.createElement('blockquote'); const p = document.createElement('p');
        appendInline(p, quote[1]); blockquote.append(p); preview.append(blockquote); continue;
      }
      const unordered = line.match(/^s*[-*+]s+(.+)$/);
      const ordered = line.match(/^s*d+[.)]s+(.+)$/);
      if (unordered || ordered) {
        flushParagraph();
        const type = unordered ? 'ul' : 'ol';
        if (!list || list.tagName.toLowerCase() !== type) {
          flushList(); list = document.createElement(type); preview.append(list);
        }
        const li = document.createElement('li'); appendInline(li, (unordered || ordered)[1]); list.append(li); continue;
      }
      flushList(); paragraph.push(line);
    }
    if (inCode) {
      const pre = document.createElement('pre'); const codeNode = document.createElement('code');
      codeNode.textContent = code.join('
'); pre.append(codeNode); preview.append(pre);
    }
    flushParagraph();
  }
  source.addEventListener('input', render);
  render();
})();
