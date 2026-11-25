const form = document.getElementById('joinForm');
const statusLine = document.getElementById('joinStatus');
const token = location.hash.slice(1);
if (!/^[a-f0-9]{64}$/.test(token)) {
  form.hidden = true;
  statusLine.textContent = 'Open the complete invitation link from the person who invited you.';
}

form.addEventListener('submit', async event => {
  event.preventDefault();
  const button = form.querySelector('button');
  button.disabled = true;
  statusLine.textContent = 'Joining…';
  try {
    const response = await charterPost('join_thread.php', {
      token,
      name: document.getElementById('joinName').value,
    });
    if (response.status !== 'success') {
      statusLine.textContent = response.message;
      return;
    }
    location.replace('index.html?uuid=' + encodeURIComponent(response.thread_uuid));
  } catch {
    statusLine.textContent = 'Could not reach CharterDesk. Try again.';
  } finally {
    button.disabled = false;
  }
});
