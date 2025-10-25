<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - GitHub訪問数集計システム</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        @keyframes wobble {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        @keyframes rainbow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .shake {
            animation: shake 0.5s;
        }
        .wobble {
            animation: wobble 1s ease-in-out infinite;
        }
        .bounce {
            animation: bounce 1s ease-in-out infinite;
        }
        .rainbow-text {
            background: linear-gradient(90deg, #ff0000, #ff7f00, #ffff00, #00ff00, #0000ff, #4b0082, #9400d3);
            background-size: 200% 200%;
            animation: rainbow 2s ease infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            top: -20px;
            z-index: 9999;
            pointer-events: none;
            animation: confetti-fall 3s linear forwards;
        }
        @keyframes confetti-fall {
            to {
                transform: translateY(calc(100vh + 50px)) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center bg-indigo-100 rounded-full">
                    <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    管理画面にログイン
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    GitHub訪問数集計システム
                </p>
            </div>

            <form class="mt-8 space-y-6" method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                
                @if(isset($isFiveStrikes) && $isFiveStrikes)
                    <div class="bg-gradient-to-r from-purple-400 via-pink-500 to-red-500 border-4 border-yellow-400 text-white px-6 py-4 rounded-lg relative wobble" role="alert">
                        <div class="flex items-center space-x-3">
                            <div class="text-4xl bounce">🎯</div>
                            <div>
                                <p class="font-bold text-xl rainbow-text">おや？{{ $consecutiveFailures }}回連続で失敗しましたね？</p>
                                <p class="mt-2 text-sm">もしかして...パスワード忘れちゃいました？ 🤔</p>
                                <p class="mt-1 text-sm">深呼吸して、もう一度試してみましょう！ 💪</p>
                                <p class="mt-2 text-xs italic">ヒント：CapsLockがオンになっていないか確認してみてください 😉</p>
                            </div>
                        </div>
                        <div class="absolute top-0 right-0 mt-2 mr-2">
                            <span class="text-2xl">🎊</span>
                        </div>
                    </div>
                @elseif(isset($consecutiveFailures) && $consecutiveFailures >= 3)
                    <div class="bg-yellow-100 border-2 border-yellow-400 text-yellow-800 px-4 py-3 rounded relative" role="alert">
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl">⚠️</span>
                            <p>あと{{ 5 - $consecutiveFailures }}回失敗すると...何かが起こる！？</p>
                        </div>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert" id="errorBox">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">メールアドレス</label>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            autocomplete="email" 
                            required 
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm @error('email') border-red-500 @enderror" 
                            placeholder="メールアドレス"
                            value="{{ old('email') }}"
                        >
                    </div>
                    <div>
                        <label for="password" class="sr-only">パスワード</label>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="current-password" 
                            required 
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm @error('password') border-red-500 @enderror" 
                            placeholder="パスワード"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            id="remember" 
                            name="remember" 
                            type="checkbox" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            ログイン状態を保持する
                        </label>
                    </div>
                </div>

                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-indigo-500 group-hover:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                        ログイン
                    </button>
                </div>

            </form>
        </div>
    </div>
    
    <!-- Blade変数をデータ属性として埋め込む -->
    <div id="login-state" 
         data-five-strikes="{{ isset($isFiveStrikes) && $isFiveStrikes ? '1' : '0' }}"
         data-has-errors="{{ $errors->any() ? '1' : '0' }}"
         style="display: none;">
    </div>
    
    <script>
        // データ属性から変数を読み取る
        const loginState = document.getElementById('login-state');
        const isFiveStrikes = loginState.getAttribute('data-five-strikes') === '1';
        const hasErrors = loginState.getAttribute('data-has-errors') === '1';
        
        // ランダムな紙吹雪を生成する関数
        function createConfetti() {
            const colors = [
                '#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff',
                '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#6c5ce7', '#fd79a8',
                '#a29bfe', '#fd79a8', '#fdcb6e', '#e17055', '#74b9ff', '#55efc4'
            ];
            const shapes = ['●', '★', '♥', '◆', '■', '▲'];
            
            for (let i = 0; i < 80; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                
                // ランダムな位置
                confetti.style.left = Math.random() * 100 + '%';
                
                // ランダムな色
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                
                // ランダムなサイズ
                const size = Math.random() * 10 + 5;
                confetti.style.width = size + 'px';
                confetti.style.height = size + 'px';
                
                // ランダムな形状（一部は記号を使用）
                if (Math.random() > 0.5) {
                    confetti.style.borderRadius = '50%';
                }
                
                // ランダムな記号を追加
                if (Math.random() > 0.7) {
                    confetti.textContent = shapes[Math.floor(Math.random() * shapes.length)];
                    confetti.style.background = 'transparent';
                    confetti.style.color = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.fontSize = (size * 2) + 'px';
                    confetti.style.lineHeight = '1';
                }
                
                // ランダムなアニメーション遅延と期間
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.animationDuration = (Math.random() * 2 + 3) + 's';
                
                document.body.appendChild(confetti);
                
                // アニメーション終了後に削除
                setTimeout(() => {
                    confetti.remove();
                }, (parseFloat(confetti.style.animationDelay) + parseFloat(confetti.style.animationDuration)) * 1000 + 100);
            }
        }
        
        // 効果音を再生する関数
        function playLoginFailureSounds() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const playTone = (frequency, duration) => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = frequency;
                    oscillator.type = 'sine';
                    
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + duration);
                };
                
                // 楽しいメロディを再生
                setTimeout(() => playTone(523.25, 0.2), 0);    // C
                setTimeout(() => playTone(659.25, 0.2), 200);  // E
                setTimeout(() => playTone(783.99, 0.2), 400);  // G
                setTimeout(() => playTone(1046.50, 0.3), 600); // C (高)
            } catch (e) {
                // AudioContextがサポートされていない場合は無視
                console.log('Audio playback not supported');
            }
        }
        
        // 5回失敗時の特別なエフェクト
        if (isFiveStrikes) {
            // エラーボックスにシェイクアニメーションを追加
            const errorBox = document.getElementById('errorBox');
            if (errorBox) {
                errorBox.classList.add('shake');
            }
            
            // ページロード時に紙吹雪を表示
            createConfetti();
            
            // 連続で紙吹雪を降らせる
            setTimeout(() => createConfetti(), 1000);
            setTimeout(() => createConfetti(), 2000);
            
            // 効果音を再生
            playLoginFailureSounds();
        }
        
        // エラーメッセージがある場合、フォームにシェイクを追加
        if (hasErrors) {
            const form = document.getElementById('loginForm');
            if (form) {
                form.classList.add('shake');
                setTimeout(() => {
                    form.classList.remove('shake');
                }, 500);
            }
        }
    </script>
</body>
</html>
