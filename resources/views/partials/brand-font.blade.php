{{-- Nexus ロゴ用フォント（Syncopate）＋共通スタイル。各レイアウトの <head> で読み込む --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&display=swap" rel="stylesheet">
<style>
    /* Nexus ロゴ：ワイドな字間＋青→紫グラデーション */
    .brand-logo {
        font-family: 'Syncopate', sans-serif;
        font-weight: 700;
        letter-spacing: 0.22em;
        padding-right: 0.22em; /* グラデーションの末尾欠け防止 */
        background: linear-gradient(90deg, #60a5fa 0%, #a78bfa 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
    }

    /* タグラインの頭文字アクセント（ロゴと同じフォント・確実に表示されるベタ塗り） */
    .brand-accent {
        font-family: 'Syncopate', sans-serif;
        font-weight: 700;
        color: #93c5fd; /* blue-300 */
    }
</style>
