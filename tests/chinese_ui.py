"""Rendered route-level Chinese heading regression (no browser simulation)."""
import re
import urllib.request
from html.parser import HTMLParser

class Headings(HTMLParser):
    def __init__(self):
        super().__init__(); self.in_h1 = False; self.headings = []; self.current = ''
    def handle_starttag(self, tag, attrs):
        if tag == 'h1': self.in_h1 = True; self.current = ''
    def handle_data(self, data):
        if self.in_h1: self.current += data
    def handle_endtag(self, tag):
        if tag == 'h1': self.in_h1 = False; self.headings.append(re.sub(r'\s+', '', self.current))

routes = {'/':'2026世界杯','/teams':'参赛球队','/players':'球员名单','/worldcup':'赛程与比分','/worldcup/standings':'赛程与比分','/news':'足球新闻','/forum':'球迷论坛','/login':'账号登录','/register':'注册账号','/search':'全站搜索','/commentary':'智能解说','/vision':'图像工作室','/deliverables':'课程作业下载','/countries':'国家与主办城市'}
opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))
for path, expected in routes.items():
    with opener.open('http://127.0.0.1:8088' + path, timeout=45) as response:
        body = response.read().decode('utf-8'); parser = Headings(); parser.feed(body)
        assert parser.headings == [expected], (path, parser.headings, expected)
        assert not any(s in body for s in ['为你的热爱','每个号码，','看台，有回音','不必相识','FIND 账号设置','THE WORLD.','ONE GAME.'])
        assert ' · 世界杯宇宙</title>' in body
print('PASS 14 Chinese UI routes; functional Chinese headings; rejected slogans absent.')
