import { createApp } from '../../server/app.mjs';
import { withBlobDatabase } from '../../server/blob-db.mjs';
const mysqlApp = createApp();
export default async function app(request) {
  if (process.env.STORAGE_BACKEND !== 'netlify-blobs') return mysqlApp(request);
  try {
    return await withBlobDatabase(db => createApp({ database: () => db })(request.clone()));
  } catch (error) {
    console.error('Persistent storage failed:', error.code || error.message);
    return new Response('Service temporarily unavailable. Please try again later.', {
      status: 503, headers: { 'Content-Type': 'text/plain', 'Cache-Control': 'no-store', 'X-Content-Type-Options': 'nosniff' }
    });
  }
}
