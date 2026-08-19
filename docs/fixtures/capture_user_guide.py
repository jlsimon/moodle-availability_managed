#!/usr/bin/env python3
"""Capture the illustrated guide from the local Moodle installation.

Uses only Python's standard library and the system ChromeDriver.
"""

import base64
import json
import os
import pathlib
import subprocess
import sys
import time
import urllib.error
import urllib.request


BASE = os.environ.get("MOODLE_GUIDE_URL", "https://atudemos.mudel.es")
ROOT = pathlib.Path(__file__).resolve().parents[1]
COURSE_FILE = ROOT / "fixtures" / ".courseid"
COURSE_ID = os.environ.get(
    "MOODLE_GUIDE_COURSE",
    COURSE_FILE.read_text().strip() if COURSE_FILE.is_file() else "105",
)
ADMIN_USER = os.environ.get("MOODLE_ADMIN_USER", "admin")
ADMIN_PASSWORD = os.environ["MOODLE_ADMIN_PASSWORD"]
DEMO_PASSWORD = os.environ.get("MOODLE_GUIDE_PASSWORD", "Guide2026!")


class Browser:
    def __init__(self, port):
        self.endpoint = f"http://127.0.0.1:{port}"
        self.driver = subprocess.Popen(
            ["chromedriver", f"--port={port}", "--silent"],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
        for _ in range(50):
            try:
                self._request("GET", "/status")
                break
            except Exception:
                time.sleep(0.1)
        result = self._request("POST", "/session", {
            "capabilities": {"alwaysMatch": {"goog:chromeOptions": {"args": [
                "--headless=new", "--no-sandbox", "--disable-dev-shm-usage",
                "--ignore-certificate-errors", "--window-size=1440,1050",
                "--force-device-scale-factor=1",
            ]}}}
        })
        self.session = result["sessionId"]

    def _request(self, method, path, data=None):
        payload = None if data is None else json.dumps(data).encode()
        request = urllib.request.Request(self.endpoint + path, data=payload, method=method)
        request.add_header("Content-Type", "application/json")
        with urllib.request.urlopen(request, timeout=30) as response:
            result = json.loads(response.read())
        if result.get("value") and isinstance(result["value"], dict) and result["value"].get("error"):
            raise RuntimeError(result["value"])
        return result.get("value", result)

    def command(self, method, path, data=None):
        return self._request(method, f"/session/{self.session}{path}", data)

    def go(self, url):
        self.command("POST", "/url", {"url": url})
        self.wait("return document.readyState === 'complete'")

    def script(self, source, *args):
        return self.command("POST", "/execute/sync", {"script": source, "args": list(args)})

    def wait(self, condition, timeout=20, *args):
        end = time.time() + timeout
        while time.time() < end:
            try:
                if self.script(condition, *args):
                    return
            except Exception:
                pass
            time.sleep(0.2)
        raise TimeoutError(condition)

    def login(self, username, password, lang):
        self.go(f"{BASE}/login/index.php?lang={lang}")
        self.wait("return !!document.querySelector('#username')")
        self.script(
            "document.querySelector('#username').value=arguments[0];"
            "document.querySelector('#password').value=arguments[1];"
            "document.querySelector('#loginbtn').click();",
            username, password,
        )
        self.wait("return !location.pathname.includes('/login/')", 30)

    def click(self, selector):
        self.wait("return !!document.querySelector(arguments[0])", 20, selector)
        self.script("document.querySelector(arguments[0]).click()", selector)

    def shot(self, folder, filename, selector=None):
        time.sleep(0.8)
        self.script("[...document.querySelectorAll('button')].filter(x=>/Entendido|Got it/i.test(x.innerText)).forEach(x=>x.click())")
        time.sleep(0.4)
        self.script("document.querySelectorAll('[class*=\"remotesupport\"]').forEach(x=>x.style.display='none')")
        if selector:
            self.wait("return !!document.querySelector(arguments[0])", 20, selector)
            self.script("document.querySelector(arguments[0]).scrollIntoView({block:'center'})", selector)
            time.sleep(0.3)
        data = self.command("GET", "/screenshot")
        target = ROOT / "images" / folder / filename
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(base64.b64decode(data))
        print(target.relative_to(ROOT))

    def close(self):
        try:
            self.command("DELETE", "")
        finally:
            self.driver.terminate()
            self.driver.wait(timeout=5)


def dismiss_notifications(browser):
    browser.script("document.querySelectorAll('[data-action=\"hide\"], .alert .btn-close').forEach(x=>x.click())")
    browser.script("[...document.querySelectorAll('button')].filter(x=>/Entendido|Got it/i.test(x.innerText)).forEach(x=>x.click())")
    time.sleep(0.5)


def capture_teacher(lang, folder, port):
    browser = Browser(port)
    try:
        browser.login("mavail_teacher", DEMO_PASSWORD, lang)
        dashboard = f"{BASE}/availability/condition/managed/index.php?courseid={COURSE_ID}&lang={lang}"
        browser.go(dashboard)
        browser.wait("return !!document.querySelector('[data-region=\"managed-dashboard\"]')")
        dismiss_notifications(browser)
        browser.shot(folder, "01-dashboard-overview.png")

        # Closed final section: one explanation, no child controls.
        browser.script("document.querySelectorAll('section.card').forEach(s=>{s.hidden=!s.dataset.searchText.includes('Final challenge')})")
        browser.shot(folder, "02-closed-section.png", "section[data-search-text*='Final challenge']")
        browser.go(dashboard)

        # Mixed group access and individual activity rules.
        browser.script("document.querySelectorAll('section.card').forEach(s=>{s.hidden=!s.dataset.searchText.includes('Team workshop')})")
        browser.shot(folder, "03-group-section-and-activities.png", "section[data-search-text*='Team workshop']")
        browser.go(dashboard)

        # Edit the Team workshop section.
        browser.click("section[data-search-text*='Team workshop'] [data-action='edit'][data-itemtype='section']")
        try:
            browser.wait("return !!document.querySelector('[data-region=\"managed-editor\"]')", 10)
        except TimeoutError:
            browser.click("section[data-search-text*='Team workshop'] [data-action='edit'][data-itemtype='section']")
            browser.wait("return !!document.querySelector('[data-region=\"managed-editor\"]')", 20)
        browser.shot(folder, "04-edit-targets-modal.png", ".modal-dialog")
        browser.script("document.querySelector('.modal [data-action=\"cancel\"]')?.click()")

        # Group perspective.
        browser.click("[data-action=\"target-scope\"][data-scope=\"group\"]")
        browser.wait("return document.querySelector('[data-action=\"target-select\"] option:nth-child(2)')")
        browser.script("let s=document.querySelector('[data-action=\"target-select\"]');s.selectedIndex=1;s.dispatchEvent(new Event('change',{bubbles:true}))")
        browser.wait("return document.querySelector('[data-region=\"target-view\"] li')")
        browser.shot(folder, "05-group-perspective.png", "[data-region=\"target-view\"]")

        # User perspective.
        browser.click("[data-action=\"target-scope\"][data-scope=\"user\"]")
        browser.wait("return document.querySelector('[data-action=\"target-select\"] option:nth-child(2)')")
        browser.script("let s=document.querySelector('[data-action=\"target-select\"]');s.selectedIndex=1;s.dispatchEvent(new Event('change',{bubbles:true}))")
        browser.wait("return document.querySelector('[data-region=\"target-view\"] li')")
        browser.shot(folder, "06-user-perspective.png", "[data-region=\"target-view\"]")

        browser.go(f"{BASE}/availability/condition/managed/help.php?courseid={COURSE_ID}&lang={lang}")
        browser.shot(folder, "07-integrated-help.png")
    finally:
        browser.close()


def capture_student(username, lang, folder, filename, port):
    browser = Browser(port)
    try:
        browser.login(username, DEMO_PASSWORD, lang)
        browser.go(f"{BASE}/course/view.php?id={COURSE_ID}&lang={lang}")
        dismiss_notifications(browser)
        browser.shot(folder, filename)
    finally:
        browser.close()


def capture_admin(lang, folder, port):
    browser = Browser(port)
    try:
        browser.login(ADMIN_USER, ADMIN_PASSWORD, lang)
        browser.go(f"{BASE}/availability/condition/managed/courseconfig.php?courseid={COURSE_ID}&lang={lang}")
        dismiss_notifications(browser)
        browser.shot(folder, "10-course-configuration.png")
        browser.go(f"{BASE}/availability/condition/managed/audit.php?courseid={COURSE_ID}&lang={lang}")
        browser.shot(folder, "11-audit-log.png")
    finally:
        browser.close()


if __name__ == "__main__":
    requested = sys.argv[1] if len(sys.argv) > 1 else "all"
    if requested in ("all", "es"):
        capture_teacher("es", "guide_es", 9515)
        capture_student("mavail_ana", "es", "guide_es", "08-student-ana-view.png", 9516)
        capture_student("mavail_bruno", "es", "guide_es", "09-student-bruno-view.png", 9517)
        capture_admin("es", "guide_es", 9518)
    if requested in ("all", "en"):
        capture_teacher("en", "guide", 9525)
        capture_student("mavail_ana", "en", "guide", "08-student-ana-view.png", 9526)
        capture_student("mavail_bruno", "en", "guide", "09-student-bruno-view.png", 9527)
    capture_admin("en", "guide", 9528)
