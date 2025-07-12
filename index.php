<?php
require_once 'auth.php';

$messages = [];
if ($db) {
    $stmt = $db->query("SELECT username, message, timestamp FROM messages ORDER BY timestamp ASC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Enzonic Chaos: A real-time chat application.">
    <meta name="keywords" content="chat, real-time, PHP, SQLite, messaging">
    <meta name="author" content="Cline">
    <title>Enzonic Chaos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
            <div class="header">
                <h1>Enzonic Chaos</h1>
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="sign_out.php" class="sign-out-button">Sign Out</a>
                <?php endif; ?>
            </div>

        <?php if (!isset($_SESSION['username'])): ?>
            <div class="username-form">
                <h2>Welcome to Enzonic Chaos!</h2>
                <?php if (!empty($error_message)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
<form id="auth-form" method="post" action="auth.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <label for="terms-agree" class="terms-checkbox-label">
                        <input type="checkbox" id="terms-agree" name="terms_agree">
                        I agree to the <a href="terms.php" target="_blank">Terms of Service</a>
                    </label>
                    <button type="submit" name="action" value="login">Login</button>
                    <button type="submit" name="action" value="register">Sign Up</button>
                </form>
                <div id="loading-spinner" class="loading-spinner" style="display: none;"></div>
            </div>
        <?php else: ?>
            <div class="chat-container">
                <div id="chatbox" class="chatbox">
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $isCurrentUser = false;
                        if (isset($_SESSION['username']) && $_SESSION['username'] === $msg['username']) {
                            $isCurrentUser = true;
                        }
                        ?>
                        <div class="message <?php echo $isCurrentUser ? 'my-message' : 'other-message'; ?>">
                            <strong><?php echo htmlspecialchars($msg['username']); ?></strong>
                                <div class="message-content"><?php echo $msg['message']; ?></div>
                            <span class="timestamp"><?php echo $msg['timestamp']; ?></span>
                        </div>
                    <?php endforeach; ?>
                    <button id="scrollToBottomBtn" class="scroll-to-bottom-btn" title="Scroll to bottom">
                        &#x2193;
                    </button>
                </div>
                <div class="input-area">
                    <form id="message-form" method="post" action="post_message.php" class="message-form">
                        <input type="text" name="message" placeholder="Type your message..." required id="message-input">
                        <button type="submit">Send</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const authForm = document.getElementById('auth-form');
        const loadingSpinner = document.getElementById('loading-spinner');

        if (authForm) {
            authForm.addEventListener('submit', function(event) {
                const action = event.submitter.value;
                
                if (action === 'register' && !document.getElementById('terms-agree').checked) {
                    alert('You must agree to the Terms of Service to register.');
                    event.preventDefault();
                    return;
                }
                loadingSpinner.style.display = 'block';
            });
        }
    </script>
<?php if (isset($_SESSION['username'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatbox = document.getElementById('chatbox');
            const messageInput = document.getElementById('message-input');
            const messageForm = document.getElementById('message-form');
            const scrollToBottomBtn = document.getElementById('scrollToBottomBtn');

            let typingTimeout;
            const typingIndicator = document.createElement('div');
            typingIndicator.id = 'typing-indicator';
            typingIndicator.style.fontStyle = 'italic';
            typingIndicator.style.color = '#888';
            typingIndicator.style.padding = '5px 0';
            typingIndicator.style.display = 'none';

            if (chatbox) {
                chatbox.parentNode.insertBefore(typingIndicator, chatbox.nextSibling);
            }

            let lastMessageId = 0; // Initialize lastMessageId

            if (messageInput) {
                messageInput.addEventListener('input', () => {
                    clearTimeout(typingTimeout);
                    const message = messageInput.value.trim();

                    if (message.length > 0) {
                        fetch('post_message.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                'action': 'typing',
                                'username': '<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>'
                            })
                        })
                        .catch(error => console.error('Error sending typing status:', error));

                        typingTimeout = setTimeout(() => {
                            fetch('post_message.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    'action': 'stop_typing',
                                    'username': '<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>'
                                })
                            })
                            .catch(error => console.error('Error sending stop typing status:', error));
                            typingIndicator.style.display = 'none';
                        }, 1000);
                    } else {
                        fetch('post_message.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                'action': 'stop_typing',
                                'username': '<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>'
                            })
                        })
                        .catch(error => console.error('Error sending stop typing status:', error));
                        typingIndicator.style.display = 'none';
                    }
                });
            }

            function fetchMessages() {
                let url = 'get_messages.php';
                if (lastMessageId > 0) {
                    url += `?last_id=${lastMessageId}`;
                }
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(response => {
                        if (response.error) {
                            console.error('Error fetching messages:', response.error);
                            return;
                        }

                        const messages = response.messages;
                        const typingUsers = response.typing_users;

                        const wasScrolledToBottom = chatbox.scrollHeight - chatbox.clientHeight <= chatbox.scrollTop + 1;

                        if (lastMessageId === 0) {
                            chatbox.innerHTML = '';
                        }

                        let newMessagesAdded = false;
                        messages.forEach(msg => {
                            if (msg.id > lastMessageId) {
                                const messageDiv = document.createElement('div');
                                messageDiv.classList.add('message');
                                const isCurrentUser = '<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>' === msg.username;
                                messageDiv.classList.add(isCurrentUser ? 'my-message' : 'other-message');

                                const usernameStrong = document.createElement('strong');
                                usernameStrong.textContent = msg.username;

                                const messageContentDiv = document.createElement('div');
                                messageContentDiv.classList.add('message-content');
                                messageContentDiv.textContent = msg.message;

                                const timestampSpan = document.createElement('span');
                                timestampSpan.classList.add('timestamp');
                                timestampSpan.textContent = msg.timestamp;

                                messageDiv.appendChild(usernameStrong);
                                messageDiv.appendChild(messageContentDiv);
                                messageDiv.appendChild(timestampSpan);

                                chatbox.appendChild(messageDiv);
                                newMessagesAdded = true;
                            }
                        });

                        if (messages.length > 0) {
                            lastMessageId = messages[messages.length - 1].id;
                            chatbox.scrollTop = chatbox.scrollHeight;
                        }

                        if (typingUsers && typingUsers.length > 0) {
                            typingIndicator.textContent = typingUsers.join(', ') + ' are typing...';
                            typingIndicator.style.display = 'block';
                        } else {
                            typingIndicator.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching messages:', error);
                    });
            }

            function sendMessage() {
                const message = messageInput.value.trim();
                const charLimit = 280;

                if (message === '') {
                    alert('Message cannot be empty.');
                    return;
                }
                if (message.length > charLimit) {
                    alert(`Message exceeds the character limit of ${charLimit}.`);
                    return;
                }

                fetch('post_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'message': message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        fetchMessages();
                    } else {
                        console.error('Failed to send message:', data.message);
                        alert('Failed to send message: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Error sending message: ' + error.message);
                });
            }

            if (messageForm) {
                messageForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    sendMessage();
                });
            }

            fetchMessages();

            setInterval(fetchMessages, 5000);

            if (scrollToBottomBtn) {
                scrollToBottomBtn.addEventListener('click', () => {
                    chatbox.scrollTop = chatbox.scrollHeight;
                });
            }

            if (chatbox) {
                chatbox.addEventListener('scroll', () => {
                    const isScrolledToBottom = chatbox.scrollHeight - chatbox.clientHeight <= chatbox.scrollTop + 1;
                    if (scrollToBottomBtn) {
                        if (isScrolledToBottom) {
                            scrollToBottomBtn.style.display = 'none';
                        } else {
                            scrollToBottomBtn.style.display = 'block';
                        }
                    }
                });
            }
        });
    </script>
<?php endif; ?>
