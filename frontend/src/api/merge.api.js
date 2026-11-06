import { BASE_URL, getCsrfToken, parseResponse, send } from './client';

export async function mergeCustomers(masterId, sourceId) {
  // 1. Obtain CSRF token using the current session
  const csrfToken = await getCsrfToken();

  // 2. Send Merge request
  const response = await send(`${BASE_URL}/customers/merge`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken,
    },
    body: JSON.stringify({
      master_customer_id: masterId,
      source_customer_id: sourceId,
    }),
  });

  // 3. Return the full body ({ message, data }) as before;
  //    empty or non-JSON responses become a readable error (BUG-04)
  return parseResponse(response);
}
