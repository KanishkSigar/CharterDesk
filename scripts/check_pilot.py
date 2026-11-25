"""Exercise the invitation flow against a disposable CharterDesk instance."""

import http.cookiejar
import json
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE = sys.argv[1].rstrip("/") + "/"


class BrowserSession:
    def __init__(self):
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
        )

    def request(self, endpoint, expected=200, *, form=None, data=None, authorize=True):
        headers = {}
        body = None
        if form is not None:
            headers["Content-Type"] = "application/x-www-form-urlencoded"
            body = urllib.parse.urlencode(form).encode()
        if data is not None:
            headers["Content-Type"] = "application/json"
            body = json.dumps(data).encode()
        if body is not None and authorize:
            headers["X-CharterDesk-Request"] = "1"
        request = urllib.request.Request(BASE + endpoint, body, headers)
        try:
            response = self.opener.open(request, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            payload = response.read()
            assert response.status == expected, (endpoint, response.status, payload)
            if response.headers.get_content_type() == "application/json":
                return json.loads(payload)
            return payload


host, guest, outsider = (BrowserSession() for _ in range(3))
fixture = {"created_by": "Pilot Owner", "title": "Fictional fixture"}
host.request("create_thread.php", 403, form=fixture, authorize=False)
uuid = host.request("create_thread.php", form=fixture)["thread_uuid"]
thread = {"thread_uuid": uuid}
query = urllib.parse.urlencode({"uuid": uuid})

assert not outsider.request("dashboard_data.php")["threads"]
outsider.request(f"get_thread.php?{query}", 404)
outsider.request(f"generate_recap.php?{query}", 404)

initial = host.request("invite_thread.php", data=thread)["token"]
token = host.request("invite_thread.php", data=thread)["token"]
guest.request("join_thread.php", 404, data={"token": initial, "name": "Pilot Guest"})
guest.request("join_thread.php", data={"token": token, "name": "Pilot Guest"})
outsider.request("join_thread.php", 404, data={"token": token, "name": "Third Party"})
assert guest.request("dashboard_data.php")["threads"][0]["can_manage"] is False
guest.request("thread_admin.php", 404, data={**thread, "action": "archive"})

host.request("save_offer.php", data={
    **thread, "role": "Ship Owner", "data": {"freight": "USD 7.00 PMT"},
})
offers = guest.request(f"get_thread.php?{query}")["offers"]
assert offers[0]["party"] == "Pilot Owner"
guest.request("save_offer.php", data={
    **thread, "role": "Charterer", "data": {"freight": "USD 6.25 PMT"},
})
offers = host.request(f"get_thread.php?{query}")["offers"]
assert offers[-1]["party"] == "Pilot Guest"
accept = {**thread, "offer_id": offers[-1]["id"]}
host.request("accept_offer.php", data=accept)
host.request("accept_offer.php", data=accept)
recap = guest.request(f"generate_recap.php?{query}")
assert b"STATUS: AGREED" in recap and b"USD 6.25 PMT" in recap
print("Private invitation, participant access, versioned offers, acceptance and recap passed.")
