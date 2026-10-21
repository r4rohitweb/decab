import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { createApp } from '../server/app.mjs';
const app = createApp();
createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://localhost:8888');
    const assets = { '/assets/css/style.css': 'text/css', '/assets/js/admin.js': 'text/javascript' };
    if (Object.hasOwn(assets, url.pathname)) {
      res.setHeader('Content-Type', assets[url.pathname]);
      return res.end(await readFile('.' + url.pathname));
    }
    const chunks = [];
    for await (const chunk of req) chunks.push(chunk);
    const response = await app(new Request(url, {
      method: req.method, headers: req.headers,
      ...(!['GET', 'HEAD'].includes(req.method) ? { body: Buffer.concat(chunks) } : {})
    }));
    res.writeHead(response.status, Object.fromEntries(response.headers));
    res.end(Buffer.from(await response.arrayBuffer()));
  } catch (error) { console.error(error); res.writeHead(500); res.end('Server error'); }
}).listen(8888, () => console.log('DeCab: http://localhost:8888'));
