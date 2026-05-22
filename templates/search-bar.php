<?php if (!defined('ABSPATH')) exit; ?>
<div class="bm-search" id="bmSearch">
    <form class="bm-search-form" id="bmSearchForm" method="get" target="_blank">
        <div class="bm-search-engine" id="bmSearchEngine">
            <button type="button" class="bm-search-engine-btn" id="bmSearchEngineBtn">
                <span class="bm-search-engine-icon bm-search-engine-icon--baidu" id="bmSearchEngineIcon">B</span>
                <svg class="bm-search-engine-arrow" width="10" height="10" viewBox="0 0 12 12"><path d="M3 5l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
            </button>
        </div>
        <input type="text" class="bm-search-input" id="bmSearchInput" name="wd" placeholder="搜索内容..." autocomplete="off">
        <button type="submit" class="bm-search-submit">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </button>
    </form>
    <div class="bm-search-engine-dropdown" id="bmSearchEngineDropdown">
        <div class="bm-search-engine-item is-active" data-key="baidu" data-name="百度" data-url="https://www.baidu.com/s?wd=">
            <span class="bm-engine-icon bm-engine-icon--baidu">B</span>
            <span>百度</span>
        </div>
        <div class="bm-search-engine-item" data-key="google" data-name="Google" data-url="https://www.google.com/search?q=">
            <span class="bm-engine-icon bm-engine-icon--google">G</span>
            <span>Google</span>
        </div>
        <div class="bm-search-engine-item" data-key="bing" data-name="必应" data-url="https://www.bing.com/search?q=">
            <span class="bm-engine-icon bm-engine-icon--bing">B</span>
            <span>必应</span>
        </div>
        <div class="bm-search-engine-item" data-key="sogou" data-name="搜狗" data-url="https://www.sogou.com/web?query=">
            <span class="bm-engine-icon bm-engine-icon--sogou">S</span>
            <span>搜狗</span>
        </div>
    </div>
</div>
