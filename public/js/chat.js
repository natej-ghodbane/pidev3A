document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const chatMessages = document.getElementById('chat-messages');
    const loadingIndicator = document.getElementById('loading-indicator');

    // Hide loading indicator initially
    loadingIndicator.style.display = 'none';

    // Function to add a message to the chat
    function addMessage(message, isUser = true) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message');
        messageElement.classList.add(isUser ? 'user-message' : 'assistant-message');
        messageElement.textContent = message;
        chatMessages.appendChild(messageElement);
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Handle form submission
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;

        // Add user message to chat
        addMessage(message, true);
        
        // Clear input
        messageInput.value = '';
        
        // Show loading indicator
        loadingIndicator.style.display = 'block';
        
        try {
            // Send message to server
            const response = await fetch('/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            
            // Add response to chat
            addMessage(data.response, false);
        } catch (error) {
            console.error('Error:', error);
            addMessage('Sorry, there was an error sending your message.', false);
        } finally {
            // Hide loading indicator
            loadingIndicator.style.display = 'none';
        }
    });

    // Function to fetch chat history
    async function loadChatHistory() {
        try {
            const response = await fetch('/chat/history');
            if (!response.ok) {
                throw new Error('Failed to load chat history');
            }
            
            const messages = await response.json();
            messages.forEach(msg => {
                addMessage(msg.content, msg.isUser);
            });
        } catch (error) {
            console.error('Error loading chat history:', error);
            addMessage('Failed to load chat history', false);
        }
    }

    // Load chat history when page loads
    loadChatHistory();
}); 