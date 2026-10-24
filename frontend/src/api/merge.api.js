export async function mergeCustomers(masterId, sourceId) {
  // 1. Obtain CSRF token using the current session
  const csrfResponse = await fetch('/api/auth/csrf', {
    credentials: 'include',
  });

  if (!csrfResponse.ok) {
    throw new Error('Khong the lay CSRF token');
  }

  const csrfResult = await csrfResponse.json();
  const csrfToken = csrfResult.data?.csrf_token;

  if (!csrfToken) {
    throw new Error('CSRF token khong hop le');
  }

  // 2. Send Merge request
  const response = await fetch('/api/customers/merge', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken,
    },
    body: JSON.stringify({
      master_customer_id: masterId,
      source_customer_id: sourceId,
    }),
  });

  const result = await response.json();

  if (!response.ok) {
    throw new Error(
      result.message || `Merge failed: HTTP ${response.status}`
    );
  }

  return result;
}
