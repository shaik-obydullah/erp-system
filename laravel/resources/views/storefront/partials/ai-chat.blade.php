{{-- AI Assistant Chat Widget --}}
<div x-data="aiChat()" x-init="checkStatus()" class="fixed bottom-6 right-6 z-50">

    <!-- Chat Toggle Button -->
    <button @click="toggleChat()"
        :class="open ? 'hidden' : ''"
        class="bg-primary-600 hover:bg-primary-700 text-white rounded-full p-4 shadow-lg transition-all duration-300 hover:scale-110">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
    </button>

    <!-- Chat Window -->
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        class="w-96 h-[500px] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="bg-primary-600 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 00.659 1.591L19 14.5M14.25 3.104c.251.023.501.05.75.082M19 14.5l-2.47 2.47a3.004 3.004 0 01-2.121.879 3.004 3.004 0 01-2.121-.879L10 14.5m9 0H1" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-sm">ShopHub Assistant</h3>
                    <p class="text-xs text-white/70" x-text="available ? 'Online' : 'Offline'"></p>
                </div>
            </div>
            <button @click="toggleChat()" class="text-white/80 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50" x-ref="messagesContainer">
            <!-- Welcome Message -->
            <template x-if="messages.length === 0">
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 00.659 1.591L19 14.5M14.25 3.104c.251.023.501.05.75.082M19 14.5l-2.47 2.47a3.004 3.004 0 01-2.121.879 3.004 3.004 0 01-2.121-.879L10 14.5m9 0H1" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-2">Hi there!</h4>
                    <p class="text-sm text-gray-500 px-4">I can help you find products, answer questions about orders, or provide recommendations.</p>
                    <div class="mt-4 space-y-2 px-4">
                        <button @click="sendMessage('Show me popular products')"
                            class="w-full text-left px-3 py-2 bg-white rounded-lg border border-gray-200 text-sm text-gray-700 hover:border-primary-500 hover:text-primary-600 transition">
                            🔍 Find products for me
                        </button>
                        <button @click="sendMessage('What are your shipping options?')"
                            class="w-full text-left px-3 py-2 bg-white rounded-lg border border-gray-200 text-sm text-gray-700 hover:border-primary-500 hover:text-primary-600 transition">
                            📦 Shipping information
                        </button>
                        <button @click="sendMessage('How do I return an item?')"
                            class="w-full text-left px-3 py-2 bg-white rounded-lg border border-gray-200 text-sm text-gray-700 hover:border-primary-500 hover:text-primary-600 transition">
                            ↩️ Return policy
                        </button>
                    </div>
                </div>
            </template>

            <!-- Message List -->
            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                        ? 'bg-primary-600 text-white rounded-2xl rounded-br-md'
                        : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-bl-md'"
                        class="max-w-[80%] px-4 py-3 shadow-sm">
                        <p class="text-sm whitespace-pre-wrap" x-html="formatMessage(msg.content)"></p>
                        <p class="text-[10px] mt-1 opacity-50" x-text="msg.time"></p>
                    </div>
                </div>
            </template>

            <!-- Typing Indicator -->
            <div x-show="loading" class="flex justify-start">
                <div class="bg-white border border-gray-200 rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 p-3 bg-white">
            <form @submit.prevent="sendMessage(userInput)" class="flex gap-2">
                <input type="text" x-model="userInput" placeholder="Ask me anything..."
                    :disabled="loading"
                    class="flex-1 px-4 py-2.5 bg-gray-100 border-0 rounded-full text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none disabled:opacity-50">
                <button type="submit" :disabled="loading || !userInput.trim()"
                    class="px-4 py-2.5 bg-primary-600 text-white rounded-full hover:bg-primary-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <p class="text-[10px] text-gray-400 text-center mt-2">Powered by AI • Responses may vary</p>
        </div>
    </div>
</div>

<script>
function aiChat() {
    return {
        open: false,
        available: false,
        loading: false,
        userInput: '',
        messages: [],

        async checkStatus() {
            try {
                const res = await fetch('/api/v1/ai/status');
                const data = await res.json();
                this.available = data.available;
            } catch (e) {
                this.available = false;
            }
        },

        toggleChat() {
            this.open = !this.open;
        },

        async sendMessage(text) {
            const message = text || this.userInput.trim();
            if (!message || this.loading) return;

            this.messages.push({
                role: 'user',
                content: message,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            });

            this.userInput = '';
            this.loading = true;

            this.$nextTick(() => {
                this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
            });

            try {
                const res = await fetch('/api/v1/ai/customer-support', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: message })
                });

                const data = await res.json();

                if (res.ok && data.response) {
                    this.messages.push({
                        role: 'assistant',
                        content: data.response,
                        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    });
                } else {
                    this.messages.push({
                        role: 'assistant',
                        content: 'Sorry, I encountered an error. Please try again later.',
                        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    });
                }
            } catch (e) {
                this.messages.push({
                    role: 'assistant',
                    content: 'Sorry, I could not connect to our support system. Please try again.',
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });
            }

            this.loading = false;

            this.$nextTick(() => {
                this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
            });
        },

        formatMessage(text) {
            return text
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>');
        }
    }
}
</script>
