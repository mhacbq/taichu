import json
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:3001/api/admin'
COOKIE = ''


def request(method, url, data=None, headers=None):
    global COOKIE
    body = None
    final_headers = {'User-Agent': 'WorkBuddy-Debugger'}
    if headers:
        final_headers.update(headers)
    if COOKIE:
        final_headers['Cookie'] = COOKIE
    if data is not None:
        if final_headers.get('Content-Type') == 'application/json':
            body = json.dumps(data, ensure_ascii=False).encode('utf-8')
        else:
            body = urllib.parse.urlencode(data).encode('utf-8')
    req = urllib.request.Request(url, data=body, headers=final_headers, method=method.upper())
    with urllib.request.urlopen(req) as resp:
        set_cookie = resp.headers.get('Set-Cookie')
        if set_cookie:
            COOKIE = set_cookie.split(';', 1)[0]
        text = resp.read().decode('utf-8', errors='replace')
        return resp.status, text

status, text = request('POST', f'{BASE}/auth/login', {'username': 'admin', 'password': 'admin123'})
print('LOGIN_STATUS', status)
print(text)

token = ''
try:
    token = json.loads(text).get('data', {}).get('token', '')
except Exception:
    token = ''

auth_headers = {'Authorization': f'Bearer {token}'} if token else {}

checks = [
    ('POINTS_ADJUST', 'POST', f'{BASE}/points/adjust', {'user_id': 3, 'type': 'add', 'amount': 1, 'reason': 'WorkBuddy调试'}, True),
    ('ALMANAC_LIST', 'GET', f'{BASE}/content/almanac?page=1&limit=20', None, False),
    ('SHENSHA_SAVE', 'POST', f'{BASE}/system/shensha', {'name': 'WorkBuddy测试神煞', 'type': 'ping', 'category': 'qita', 'description': '调试用', 'effect': '调试用', 'sort': 99, 'status': 1}, True),
    ('SEO_SAVE', 'POST', f'{BASE}/system/seo/configs', {'route': '/workbuddy-debug', 'title': 'WorkBuddy 调试', 'description': 'WorkBuddy 调试 SEO 配置', 'keywords': ['WorkBuddy', '调试'], 'robots': 'index,follow', 'og_type': 'website', 'canonical': '', 'priority': 0.5, 'changefreq': 'weekly', 'is_active': 1}, True),
]

for name, method, url, payload, is_json in checks:
    headers = dict(auth_headers)
    if is_json:
        headers['Content-Type'] = 'application/json'
    status, text = request(method, url, payload, headers)
    print(name, status)
    print(text)
