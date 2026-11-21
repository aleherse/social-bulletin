// Renders the documentation site into dist/: a landing page at /, openapi.json with Scalar at /api/ and
// asyncapi.json with the AsyncAPI React component at /event/. Each page loads its own copy of its bundle,
// so the site works offline and needs no CDN.
import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

// The "Try it" console sends its requests here; the document itself lists no servers.
const apiUrl = process.env.API_URL ?? 'https://dev.api.social.aleherse.com';

const scalarBrowser = join('node_modules', '@scalar', 'api-reference', 'dist', 'browser');
const asyncApiComponent = join('node_modules', '@asyncapi', 'react-component');

const openApi = JSON.parse(await readFile('openapi.json', 'utf8')).info;
const asyncApi = JSON.parse(await readFile('asyncapi.json', 'utf8')).info;

await rm('dist', { recursive: true, force: true });
await mkdir('dist/api', { recursive: true });
await mkdir('dist/event', { recursive: true });

await cp(scalarBrowser, 'dist/api/scalar', {
  recursive: true,
  filter: (source) => !source.endsWith('.map'),
});
await cp('openapi.json', 'dist/api/openapi.json');

const configuration = {
  url: './openapi.json',
  servers: [{ url: apiUrl }],
};

// The session is the httpOnly `token` cookie, which a cross-origin request only carries, and the response
// only sets, when it is sent with credentials. Scalar lets the page supply the `customFetch` it sends requests with.
await writeFile(
  'dist/api/index.html',
  `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>${openApi.title}</title>
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

await cp(join(asyncApiComponent, 'browser', 'standalone', 'index.js'), 'dist/event/standalone.js');
await cp(join(asyncApiComponent, 'styles', 'default.min.css'), 'dist/event/default.min.css');
await cp('asyncapi.json', 'dist/event/asyncapi.json');

await writeFile(
  'dist/event/index.html',
  `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>${asyncApi.title}</title>
    <link rel="stylesheet" href="./default.min.css" />
  </head>
  <body>
    <div id="asyncapi"></div>
    <script src="./standalone.js"></script>
    <script>
      AsyncApiStandalone.render(
        { schema: { url: './asyncapi.json' } },
        document.getElementById('asyncapi'),
      );
    </script>
  </body>
</html>
`,
);

const documents = [
  { href: './api/', name: 'HTTP API', title: openApi.title, description: 'OpenAPI reference with a request console.' },
  {
    href: './event/',
    name: 'Realtime events',
    title: asyncApi.title,
    description: 'AsyncAPI reference for the notifications pushed to browsers.',
  },
];

await writeFile(
  'dist/index.html',
  `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Social Bulletin documentation</title>
    <style>
      :root { color-scheme: light dark; font-family: system-ui, sans-serif; }
      body { max-width: 40rem; margin: 4rem auto; padding: 0 1rem; line-height: 1.5; }
      ul { list-style: none; padding: 0; display: grid; gap: 1rem; }
      a { display: block; padding: 1rem 1.25rem; border: 1px solid currentcolor; border-radius: 0.5rem; text-decoration: none; color: inherit; }
      a:hover, a:focus-visible { border-width: 2px; padding: calc(1rem - 1px) calc(1.25rem - 1px); }
      strong { display: block; font-size: 1.125rem; }
      span { opacity: 0.75; }
    </style>
  </head>
  <body>
    <main>
      <h1>Social Bulletin documentation</h1>
      <ul>
${documents
  .map(
    ({ href, name, title, description }) =>
      `        <li><a href="${href}"><strong>${name}</strong><span>${title} — ${description}</span></a></li>`,
  )
  .join('\n')}
      </ul>
    </main>
  </body>
</html>
`,
);

console.log(`Rendered dist/: landing at /, ${openApi.title} at /api/ for ${apiUrl}, ${asyncApi.title} at /event/`);
