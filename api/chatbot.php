<?php
/**
 * Chatbot API Endpoint
 * Handles chatbot requests and provides intelligent responses
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

// Start session
session_start();

// Rate limiting
$rateLimitKey = 'chatbot_limit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'reset' => time() + 60];
}

if (time() > $_SESSION[$rateLimitKey]['reset']) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'reset' => time() + 60];
}

if ($_SESSION[$rateLimitKey]['count'] >= 20) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many requests. Please wait a moment before trying again.'
    ]);
    exit;
}

$_SESSION[$rateLimitKey]['count']++;

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Message is required.'
    ]);
    exit;
}

$userMessage = trim($data['message']);
$sessionId = $data['sessionId'] ?? session_id();

if (empty($userMessage)) {
    echo json_encode([
        'success' => false,
        'message' => 'Message cannot be empty.'
    ]);
    exit;
}

// Sanitize user message
$userMessage = htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8');

// Generate bot response based on pattern matching
$botResponse = generateBotResponse($userMessage);

// Save conversation to database
try {
    saveChatMessage($sessionId, $userMessage, $botResponse);
} catch (Exception $e) {
    error_log("Chatbot error: " . $e->getMessage());
}

// Return response
echo json_encode([
    'success' => true,
    'message' => $botResponse,
    'timestamp' => date('Y-m-d H:i:s')
]);

/**
 * Generate bot response based on user message
 */
function generateBotResponse($message) {
    $messageLower = strtolower($message);
    
    // Search functionality
    if (preg_match('/\b(search|find|looking for|look for)\b/i', $messageLower)) {
        return "🔍 You can use our search feature to find posts! Just click on the search icon in the navigation bar or visit /search.php to search for specific topics, keywords, or articles.";
    }
    
    // Latest posts
    if (preg_match('/\b(latest|recent|new|newest)\s+(post|article|blog)/i', $messageLower)) {
        try {
            $posts = getPublishedPosts(3, 0);
            if (!empty($posts)) {
                $response = "📝 Here are our latest posts:\n\n";
                foreach ($posts as $post) {
                    $response .= "• <a href='/post.php?slug=" . htmlspecialchars($post['slug']) . "' class='text-blue-600 hover:underline'>" . htmlspecialchars($post['title']) . "</a>\n";
                }
                return $response;
            }
        } catch (Exception $e) {
            error_log("Error fetching posts: " . $e->getMessage());
        }
        return "📝 Check out our blog page to see the latest posts!";
    }
    
    // Categories
    if (preg_match('/\b(categor(y|ies)|topic|section)\b/i', $messageLower)) {
        try {
            $categories = getAllCategories();
            if (!empty($categories)) {
                $response = "📂 Our blog categories:\n\n";
                foreach ($categories as $category) {
                    $response .= "• " . htmlspecialchars($category['name']);
                    if (!empty($category['description'])) {
                        $response .= " - " . htmlspecialchars($category['description']);
                    }
                    $response .= "\n";
                }
                return $response;
            }
        } catch (Exception $e) {
            error_log("Error fetching categories: " . $e->getMessage());
        }
        return "📂 We have various categories covering different tech topics. Visit our blog to explore them!";
    }
    
    // Help
    if (preg_match('/\b(help|command|what can you do|how to use)\b/i', $messageLower)) {
        return "💡 <strong>I can help you with:</strong>\n\n" .
               "• Find posts - Ask me about searching or finding articles\n" .
               "• Latest posts - Ask me about recent or new posts\n" .
               "• Categories - Ask me about blog categories or topics\n" .
               "• About - Learn more about CodeZerra\n" .
               "• Contact - Get in touch with us\n\n" .
               "Just type your question naturally, and I'll do my best to help! 😊";
    }
    
    // About
    if (preg_match('/\b(about|who|what is|tell me about)\b.*\b(codezerra|blog|site|website|you)\b/i', $messageLower)) {
        return "👋 <strong>Welcome to CodeZerra!</strong>\n\n" .
               "CodeZerra is a technology blog dedicated to sharing knowledge about web development, programming, software engineering, and the latest tech trends.\n\n" .
               "Our mission is to provide high-quality, practical content that helps developers and tech enthusiasts learn, grow, and stay updated with the ever-evolving tech landscape.\n\n" .
               "Visit our <a href='/about.php' class='text-blue-600 hover:underline'>About page</a> to learn more!";
    }
    
    // Contact
    if (preg_match('/\b(contact|email|reach|get in touch|message)\b/i', $messageLower)) {
        return "📧 <strong>Get in touch with us!</strong>\n\n" .
               "Visit our <a href='/contact.php' class='text-blue-600 hover:underline'>Contact page</a> to send us a message. We'd love to hear from you!\n\n" .
               "Whether you have questions, suggestions, or just want to say hello, feel free to reach out. 😊";
    }
    
    // Greetings
    if (preg_match('/^(hi|hello|hey|greetings|good morning|good afternoon|good evening)\b/i', $messageLower)) {
        return "👋 Hello! Welcome to CodeZerra! I'm here to help you navigate our blog and find what you're looking for. How can I assist you today?";
    }
    
    // Thanks
    if (preg_match('/\b(thanks|thank you|appreciate|thx)\b/i', $messageLower)) {
        return "😊 You're welcome! Feel free to ask if you need anything else. Happy reading!";
    }
    
    // Bye
    if (preg_match('/\b(bye|goodbye|see you|farewell)\b/i', $messageLower)) {
        return "👋 Goodbye! Come back soon. Happy coding! 💻";
    }
    
    // Default response
    return "I'm here to help! You can ask me about:\n\n" .
           "• 🔍 Searching for posts\n" .
           "• 📝 Latest articles\n" .
           "• 📂 Blog categories\n" .
           "• 📧 Contact information\n" .
           "• ℹ️ About CodeZerra\n\n" .
           "Try asking: \"Show me the latest posts\" or \"What categories do you have?\"";
}
