<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Group;
use App\Models\Project;
use App\Models\Task;
use App\Http\Resources\UserResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class UserController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');
    // }

    // 🔹 Mendapatkan semua user
    public function getUsers()
    {
        // $users = User::all();
        return UserResource::collection(User::all());
    }

    // 🔹 Mendapatkan semua grup berdasarkan userId
    public function getGroups($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $groups = $user->groups;
        return GroupResource::collection($groups);
    }

    // 🔹 Mendapatkan semua proyek berdasarkan userId
    public function getProjectsByUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $groups = $user->groups;
        $projects = collect();

        foreach ($groups as $group) {
            $projects = $projects->merge($group->projects);
        }

        return ProjectResource::collection($projects);
    }

    // 🔹 Mendapatkan semua proyek berdasarkan groupId
    public function getProjectsByGroup($userId, $groupId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $group = $user->groups()->find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }

        $projects = $group->projects;
        return ProjectResource::collection($projects);
    }

    // 🔹 Mendapatkan semua tugas berdasarkan projectId
    public function getTasks($userId, $groupId, $projectId)
    {
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        // Log untuk memverifikasi relasi
        Log::info('Project Group ID: ' . $project->group->id);
        Log::info('Project ID: ' . $groupId);
        Log::info('Project User ID: ' . $userId);
        Log::info('Project Group User ID: ' . $project->group->user->id);


        if ($project->group->id != $groupId || $project->group->user->id != $userId) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $tasks = $project->tasks;
        return TaskResource::collection($tasks);
    }


    // 🔹 Membuat user baru
    public function createUser(Request $request)
    {
        $user = User::create($request->all());
        return new UserResource($user);
    }

    // 🔹 Menghapus user berdasarkan ID
    public function deleteUser($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->delete();
            return response()->json(['message' => 'User deleted'], Response::HTTP_OK);
        }
        return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
    }

    // 🔹 Menambahkan Group ke User berdasarkan userId
    public function addGroupToUser(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $group = new Group($request->all());
        $user->groups()->save($group);

        return new GroupResource($group);
    }

    // 🔹 Menambahkan Project ke Group
    public function addProjectToGroup(Request $request, $userId, $groupId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $group = $user->groups()->find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }

        $project = new Project($request->all());
        $group->projects()->save($project);

        return new ProjectResource($project);
    }

    // 🔹 Menambahkan Task ke Project
    public function addTaskToProject(Request $request, $userId, $groupId, $projectId)
    {
        $project = Project::with('group.user')->find($projectId);

        // Debug untuk memastikan relasi sudah terambil dengan benar
        Log::info('Project and group information:', [
            'project' => $project,
            'group' => $project->group,
            'user' => $project->group->user,
        ]);

        if (!$project || !$project->group || $project->group->id !== (int) $groupId || !$project->group->user || $project->group->user->id !== (int) $userId) {
            Log::info('Forbidden Access:', [
                'projectGroupId' => $project->group->id ?? 'null',
                'projectUserId' => $project->group->user->id ?? 'null',
                'groupId' => $groupId,
                'userId' => $userId,
            ]);
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Lanjutkan proses penambahan task
        $task = new Task($request->all());
        $project->tasks()->save($task);

        return new TaskResource($task);
    }


    // 🔹 Menghapus Group
    public function deleteGroup($userId, $groupId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $group = $user->groups()->find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }

        $group->delete();
        return response()->json(['message' => 'Group deleted'], Response::HTTP_OK);
    }

    // 🔹 Menghapus Project
    public function deleteProject($userId, $groupId, $projectId)
    {
        $project = Project::find($projectId);
        if (!$project || $project->group->id != $groupId || $project->group->user->id != $userId) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $project->delete();
        return response()->json(['message' => 'Project deleted'], Response::HTTP_OK);
    }

    // 🔹 Menghapus Task
    public function deleteTask($userId, $groupId, $projectId, $taskId)
    {
        $task = Task::find($taskId);
        if (!$task || $task->project->id != $projectId || $task->project->group->id != $groupId || $task->project->group->user->id != $userId) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $task->delete();
        return response()->json(['message' => 'Task deleted'], Response::HTTP_OK);
    }

    // 🔹 Update Task
    public function updateTask(Request $request, $userId, $groupId, $projectId, $taskId)
    {
        $task = Task::find($taskId);
        if (!$task || $task->project->id != $projectId || $task->project->group->id != $groupId || $task->project->group->user->id != $userId) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $task->update($request->all());
        return new TaskResource($task);
    }

    // 🔹 Update Project
    public function updateProject(Request $request, $userId, $groupId, $projectId)
    {
        $project = Project::find($projectId);
        if (!$project || $project->group->id != $groupId || $project->group->user->id != $userId) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $project->update($request->all());
        return new ProjectResource($project);
    }
}

