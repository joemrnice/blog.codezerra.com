/**
 * CodeZerra Chatbot
 * Floating chat widget with AI-powered responses
 */

class ChatBot {
    constructor() {
        this.isOpen = false;
        this.isTyping = false;
        this.sessionId = this.getSessionId();
        this.messageHistory = [];
        this.init();
    }

    /**
     * Get or create session ID
     */
    getSessionId() {
        let sessionId = localStorage.getItem('chatbot_session_id');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('chatbot_session_id', sessionId);
        }
        return sessionId;
    }

    /**
     * Initialize chatbot
     */
    init() {
        this.createChatWidget();
        this.attachEventListeners();
        this.loadWelcomeMessage();
    }

    /**
     * Create chat widget HTML
     */
    createChatWidget() {
        const chatHTML = `
            <!-- Chatbot Toggle Button -->
            <button id="chatbot-toggle" class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-300 flex items-center justify-center z-50 group">
                <svg id="chat-icon" class="w-6 h-6 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                <svg id="close-icon" class="w-6 h-6 hidden transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="absolute top-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></span>
            </button>

            <!-- Chat Window -->
            <div id="chatbot-window" class="fixed bottom-24 right-6 w-96 max-w-[calc(100vw-3rem)] bg-white rounded-2xl shadow-2xl z-50 transform scale-0 origin-bottom-right transition-all duration-300 opacity-0 pointer-events-none">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 rounded-t-2xl flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">CodeZerra Assistant</h3>
                            <p class="text-xs text-blue-100">Online • Here to help</p>
                        </div>
                    </div>
                    <button id="minimize-chat" class="hover:bg-white/20 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Chat Messages -->
                <div id="chat-messages" class="h-96 overflow-y-auto p-4 space-y-4 bg-gray-50">
                    <!-- Messages will be inserted here -->
                </div>

                <!-- Typing Indicator -->
                <div id="typing-indicator" class="px-4 py-2 hidden">
                    <div class="flex items-center space-x-2 text-gray-500">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                        <span class="text-sm">CodeZerra is typing...</span>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="p-4 border-t border-gray-200 bg-white rounded-b-2xl">
                    <form id="chat-form" class="flex items-center space-x-2">
                        <input 
                            type="text" 
                            id="chat-input" 
                            placeholder="Type your message..." 
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            autocomplete="off"
                        />
                        <button 
                            type="submit" 
                            id="send-button"
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-2 rounded-full hover:shadow-lg transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </form>
                    <p class="text-xs text-gray-400 mt-2 text-center">Powered by CodeZerra AI</p>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', chatHTML);
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const toggleButton = document.getElementById('chatbot-toggle');
        const minimizeButton = document.getElementById('minimize-chat');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');

        toggleButton.addEventListener('click', () => this.toggleChat());
        minimizeButton.addEventListener('click', () => this.toggleChat());
        chatForm.addEventListener('submit', (e) => this.handleSubmit(e));

        // Auto-resize input
        chatInput.addEventListener('input', () => {
            chatInput.style.height = 'auto';
            chatInput.style.height = chatInput.scrollHeight + 'px';
        });
    }

    /**
     * Toggle chat window
     */
    toggleChat() {
        this.isOpen = !this.isOpen;
        const chatWindow = document.getElementById('chatbot-window');
        const chatIcon = document.getElementById('chat-icon');
        const closeIcon = document.getElementById('close-icon');

        if (this.isOpen) {
            chatWindow.classList.remove('scale-0', 'opacity-0', 'pointer-events-none');
            chatWindow.classList.add('scale-100', 'opacity-100');
            chatIcon.classList.add('hidden');
            closeIcon.classList.remove('hidden');
            document.getElementById('chat-input').focus();
        } else {
            chatWindow.classList.add('scale-0', 'opacity-0', 'pointer-events-none');
            chatWindow.classList.remove('scale-100', 'opacity-100');
            chatIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
        }
    }

    /**
     * Load welcome message
     */
    loadWelcomeMessage() {
        setTimeout(() => {
            this.addBotMessage(
                "👋 Hi there! I'm the CodeZerra Assistant. I can help you:\n\n" +
                "• Find posts and articles\n" +
                "• Discover categories\n" +
                "• Learn about CodeZerra\n" +
                "• And more!\n\n" +
                "How can I assist you today?"
            );
        }, 500);
    }

    /**
     * Handle form submit
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        const input = document.getElementById('chat-input');
        const message = input.value.trim();

        if (!message || this.isTyping) return;

        // Add user message
        this.addUserMessage(message);
        input.value = '';

        // Send to API
        await this.sendMessage(message);
    }

    /**
     * Add user message to chat
     */
    addUserMessage(message) {
        const messagesContainer = document.getElementById('chat-messages');
        const messageHTML = `
            <div class="flex justify-end animate-fade-in">
                <div class="max-w-[80%] bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-2 shadow-md">
                    <p class="text-sm whitespace-pre-wrap">${this.escapeHtml(message)}</p>
                    <span class="text-xs text-blue-100 mt-1 block">${this.getCurrentTime()}</span>
                </div>
            </div>
        `;
        
        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    /**
     * Add bot message to chat
     */
    addBotMessage(message) {
        const messagesContainer = document.getElementById('chat-messages');
        const messageHTML = `
            <div class="flex justify-start animate-fade-in">
                <div class="max-w-[80%] bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-2 shadow-md">
                    <p class="text-sm text-gray-800 whitespace-pre-wrap">${message}</p>
                    <span class="text-xs text-gray-400 mt-1 block">${this.getCurrentTime()}</span>
                </div>
            </div>
        `;
        
        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    /**
     * Show typing indicator
     */
    showTypingIndicator() {
        this.isTyping = true;
        document.getElementById('typing-indicator').classList.remove('hidden');
        document.getElementById('send-button').disabled = true;
        this.scrollToBottom();
    }

    /**
     * Hide typing indicator
     */
    hideTypingIndicator() {
        this.isTyping = false;
        document.getElementById('typing-indicator').classList.add('hidden');
        document.getElementById('send-button').disabled = false;
    }

    /**
     * Send message to API
     */
    async sendMessage(message) {
        this.showTypingIndicator();

        try {
            const response = await fetch('/api/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    sessionId: this.sessionId
                })
            });

            const data = await response.json();

            // Simulate typing delay
            await this.sleep(1000);

            this.hideTypingIndicator();

            if (data.success) {
                this.addBotMessage(data.message);
            } else {
                this.addBotMessage("Sorry, I encountered an error. Please try again.");
            }
        } catch (error) {
            console.error('Chatbot error:', error);
            this.hideTypingIndicator();
            this.addBotMessage("Oops! Something went wrong. Please try again later.");
        }
    }

    /**
     * Scroll to bottom of messages
     */
    scrollToBottom() {
        const messagesContainer = document.getElementById('chat-messages');
        setTimeout(() => {
            messagesContainer.scrollTo({
                top: messagesContainer.scrollHeight,
                behavior: 'smooth'
            });
        }, 100);
    }

    /**
     * Get current time
     */
    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Sleep utility
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Initialize chatbot when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new ChatBot();
    });
} else {
    new ChatBot();
}

// Add fade-in animation to CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
    
    #chat-messages::-webkit-scrollbar {
        width: 6px;
    }
    
    #chat-messages::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    #chat-messages::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 10px;
    }
    
    #chat-messages::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }
`;
document.head.appendChild(style);
