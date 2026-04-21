# Tasks Management API

A Laravel 12 REST API for project and task management with collaborative features.

## Features

- **User Authentication**: Register, login, email verification, password reset
- **Projects**: Create, manage, and organize projects with status tracking (active/paused/completed) and visibility settings (private/public)
- **Tasks**: Full task management within projects with priorities, due dates, and positions
- **Task Statuses**: Customizable statuses per project
- **Task Assignments**: Assign tasks to project members
- **Task Dependencies**: Define task dependencies
- **Comments**: Discuss tasks with comments
- **Notes**: Personal notes per user
- **Profiles**: User profiles with skill ratings
- **Block List**: Block other users
- **Reports**: Report inappropriate content
- **Notifications**: In-app notifications

## Tech Stack

- Laravel 12
- Laravel Sanctum (API authentication)
- SQLite/MySQL database

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## API Endpoints

### Public Routes (No Auth)
- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `POST /api/verify-email` - Verify email
- `POST /api/resend-verification` - Resend verification code
- `POST /api/forgot-password` - Request password reset
- `POST /api/reset-password` - Reset password

### Authenticated Routes
All routes require `Authorization: Bearer {token}` header.

- `POST /api/logout` - Logout
- `GET /api/me` - Get current user

### Projects
- `GET /api/my-projects` - List user's projects
- `GET /api/projects/{id}` - Get project details
- `POST /api/projects` - Create project
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project
- `PATCH /api/projects/{id}/status` - Update project status
- `PATCH /api/projects/{id}/visibility` - Update visibility
- `POST /api/projects/{id}/restore` - Restore deleted project

### Project Users
- `GET /api/projects/{id}/users` - List members
- `POST /api/projects/{id}/users` - Add user
- `PUT /api/projects/{id}/users/{userId}/role` - Update role
- `DELETE /api/projects/{id}/users/{userId}` - Remove user
- `POST /api/projects/{id}/users/leave` - Leave project
- `POST /api/projects/{id}/users/transfer-ownership/{userId}` - Transfer ownership

### Tasks
- `GET /api/projects/{id}/tasks` - List tasks
- `GET /api/projects/{id}/tasks/{task}` - Get task
- `POST /api/projects/{id}/tasks` - Create task
- `PUT /api/projects/{id}/tasks/{task}` - Update task
- `PUT /api/projects/{id}/tasks/{task}/status` - Update task status
- `DELETE /api/projects/{id}/tasks/{task}` - Delete task
- `POST /api/projects/{id}/tasks/reorder` - Reorder tasks

### Task Assignments
- `GET /api/projects/{id}/tasks/{task}/assignments` - List assignments
- `POST /api/projects/{id}/tasks/{task}/assignments` - Assign user
- `DELETE /api/projects/{id}/tasks/{task}/assignments/{userId}` - Unassign
- `GET /api/my-assigned-tasks` - Get my assigned tasks

### Task Dependencies
- `GET /api/projects/{id}/tasks/{task}/dependencies` - List dependencies
- `POST /api/projects/{id}/tasks/{task}/dependencies` - Add dependency
- `DELETE /api/projects/{id}/tasks/{task}/dependencies/{dependsOnTaskId}` - Remove dependency
- `PUT /api/projects/{id}/tasks/{task}/dependencies/{dependsOnTaskId}/type` - Update dependency type

### Task Statuses
- `GET /api/projects/{id}/statuses` - List statuses
- `POST /api/projects/{id}/statuses` - Create status
- `POST /api/projects/{id}/statuses/default` - Create default statuses
- `POST /api/projects/{id}/statuses/reorder` - Reorder statuses
- `PUT /api/projects/{id}/statuses/{status}` - Update status
- `DELETE /api/projects/{id}/statuses/{status}` - Delete status

### Comments
- `GET /api/tasks/{task}/comments` - List comments
- `POST /api/tasks/{task}/comments` - Add comment
- `GET /api/tasks/{task}/comments/{comment}` - Get comment
- `PUT /api/tasks/{task}/comments/{comment}` - Update comment
- `DELETE /api/tasks/{task}/comments/{comment}` - Delete comment

### Notes
- `GET /api/my-note` - Get my note
- `PUT /api/note` - Write/update note
- `DELETE /api/note` - Clear note

### Profiles
- `GET /api/my-profile` - Get my profile
- `GET /api/profiles/{profile}` - Get profile
- `POST /api/profiles` - Create profile
- `PUT /api/profiles/{profile}` - Update profile
- `DELETE /api/profiles/{profile}` - Delete profile
- `GET /api/profiles/{profile}/skills` - Get skills
- `POST /api/profiles/{profile}/skills` - Add skill
- `PUT /api/profiles/{profile}/skills/{skill}` - Update skill rating
- `DELETE /api/profiles/{profile}/skills/{skill}` - Remove skill
- `POST /api/profiles/block/{userId}` - Block user
- `DELETE /api/profiles/unblock/{userId}` - Unblock user
- `GET /api/profiles/blocked-users` - Get blocked users
- `GET /api/profiles/{profile}/can-message` - Check if can message
- `GET /api/profiles/{profile}/can-invite` - Check if can invite

### Notifications
- `GET /api/notifications` - Get notifications
- `PUT /api/notifications/{id}/read` - Mark as read
- `PUT /api/notifications/read-all` - Mark all as read
- `DELETE /api/notifications/{id}` - Delete notification

### Reports
- `POST /api/reports` - Create report
- `GET /api/reports` - Get all reports
- `GET /api/reports/user/{userId}` - Get user reports

## Authentication

Include token in requests:
```
Authorization: Bearer {your_token}
```

## License

MIT