# Enzonic Chaos

Enzonic Chaos is a simple, real-time chat application built with PHP and SQLite. It allows users to join a single channel, chat with each other, and see messages with timestamps. The application features a clean, green-themed design.

## Features

*   **Real-time Chat**: Communicate with other users in a shared channel.
*   **User Authentication**: Users can register and log in with a username and password.
*   **Message Persistence**: All messages are stored in an SQLite database, ensuring they are saved even after the server is stopped.
*   **Timestamps**: Each message is logged with its submission timestamp.
*   **Green Theme**: Matches enzonic color
*   **Send on Enter**: Messages can be sent by pressing the Enter key in the message input field.

## Requirements

*   **PHP**: Version 5.4 or higher (for the built-in web server).
*   **SQLite**: PHP must have SQLite support enabled.

## Setup and Installation

1.  **Clone the repository** (or download the files).
2.  **Navigate to the project directory** in your terminal:
    ```bash
    cd c:/Users/VelesBH/Documents/projects/enzonic chaos
    ```
3.  **Start the PHP development server**:
    ```bash
    php -S localhost:8000
    ```
    *   If you encounter an error like `'php' is not recognized...`, you may need to install PHP or add it to your system's PATH environment variable.

## How to Use

1.  Once the server is running, open your web browser and navigate to:
    `http://localhost:8000`
2.  You will be prompted to enter a username. Type your desired username and click "Join Chat".
3.  After setting your username, you will see the chat interface. Type your messages in the input field at the bottom and press "Send" or hit the **Enter** key to send your message.
4.  New messages will appear in the chat window with the sender's username and timestamp.

## Project Structure

*   `index.php`: The main file containing the PHP logic, HTML structure, and JavaScript for the chat functionality.
*   `auth.php`: Handles user registration and login.
*   `get_messages.php`: Fetches messages from the database.
*   `post_message.php`: Handles posting new messages and typing indicators.
*   `sign_out.php`: Handles user logout.
*   `style.css`: Contains all the styling for the application, including the green theme.
*   `terms.php`: Displays the terms of service.
*   `messages.db`: The SQLite database file where all chat messages are stored. This file will be created automatically when the application is first run.

## Contributing

Contributions are welcome! Please feel free to fork the repository and submit pull requests.

## License

This project is licensed under the MIT License.
