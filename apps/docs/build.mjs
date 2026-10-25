// Renders openapi.json with Scalar into dist/: a static page that loads its own copy of the
// Scalar bundle, so it works offline and needs no CDN.
import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

// The "Try it" console sends its requests here; the document itself lists no servers.
const apiUrl = process.env.API_URL ?? 'https://dev.api.social.aleherse.com';

const scalarBrowser = join('node_modules', '@scalar', 'api-reference', 'dist', 'browser');

await rm('dist', { recursive: true, force: true });
await mkdir('dist', { recursive: true });
await cp(scalarBrowser, 'dist/scalar', {
  recursive: true,
  filter: (source) => !source.endsWith('.map'),
});
await cp('openapi.json', 'dist/openapi.json');

const title = JSON.parse(await readFile('openapi.json', 'utf8')).info.title;
const configuration = {
  url: './openapi.json',
  servers: [{ url: apiUrl }],
};

// The session is the httpOnly `token` cookie, which a cross-origin request only carries, and the response
// only sets, when it is sent with credentials. Scalar lets the page supply the `customFetch` it sends requests with.
await writeFile(
  'dist/index.html',
  `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>${title}</title>
  </head>
  <body>
    <div id="app"></div>
    <script src="./scalar/standalone.js"></script>
    <script>
      Scalar.createApiReference('#app', {
        ...${JSON.stringify(configuration)},
        customFetch: (input, init) => fetch(input, { ...init, credentials: 'include' }),
      });
    </script>
  </body>
</html>
`,
);

console.log(`Rendered dist/index.html for ${apiUrl}`);
