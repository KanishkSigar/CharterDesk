document.getElementById('negForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  const response = await fetch('save_session.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });

  const result = await response.json();

  const box = document.getElementById('result');
  if (result.status === 'success') {
    box.innerHTML = `<p style="color:green">
      ✅ Saved successfully.<br>Session UUID: <b>${result.uuid}</b>
    </p>
    <p>You can view it later at:<br>
    <code>http://localhost/ime-negotiation/get_session.php?uuid=${result.uuid}</code></p>`;
  } else {
    box.innerHTML = `<p style="color:red">❌ Error: ${result.message}</p>`;
  }
});
