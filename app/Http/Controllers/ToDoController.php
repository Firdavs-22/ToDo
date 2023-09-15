<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\TodoList;
use App\Models\TodoCategory;
use App\Models\TodoStep;

class ToDoController extends Controller
{
    public function index($priority = null)
    {
        $list = TodoList::where('user_id', Auth::id())
            ->where('status', 1)
            ->when($priority >= 1 && $priority <= 3, function ($query) use ($priority) {
                return $query->where('task_priority', $priority);
            })
            ->orderBy('task_priority','desc')
            ->with('todoCategories')
            ->get();


        return response()->json([
            'todo_list' => $list
        ]);
    }

    public function showCategory(int $category_id, $priority = null)
    {
        $category = Category::where('id', $category_id)
            ->where('user_id', Auth::id())
            ->where('status', 1)
            ->first();

        if (!$category) {
            return response()->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $todo = TodoList::select('todo_list.*')
            ->leftJoin('todo_category', 'todo_list.id', '=', 'todo_category.todo_id')
            ->where('todo_category.category_id', $category->id)
            ->where('todo_list.status', 1)
            ->when($priority >= 1 && $priority <= 3, function ($query) use ($priority) {
                return $query->where('todo_list.task_priority', $priority);
            })->get();

        return response()->json([
            'todo' => $todo,
            'category' => $category
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|min:1',
            'description' => 'string',
            'priority' => 'integer',
            'deadline' => 'date',
            'category.*' => 'integer',
            'step.*' => 'string'
        ]);

        $todo = TodoList::create([
            'task_name' => $validatedData['name'],
            'user_id' => Auth::id(),
            'task_description' => $validatedData['description'] ?? null,
            'task_priority' => $validatedData['priority'] ?? 2,
            'task_deadline' => $validatedData['deadline'] ?? null,
            'completed' => false,
            'favorite' => false,
            'status' => 1,
            'created_date' => now()->format('Y-m-d'),
        ]);

        if (!empty($validatedData['category'])) {
            foreach ($validatedData['category'] as $categoryId) {
                $categoryExists = Category::where('id', $categoryId)
                    ->where('status', 1)
                    ->where('user_id', Auth::id())
                    ->first();

                if ($categoryExists) {
                    TodoCategory::create([
                        'todo_id' => $todo->id,
                        'category_id' => $categoryId,
                        'status' => 1,
                    ]);
                }
            }
        }

        if (!empty($validatedData['step'])) {
            foreach ($validatedData['step'] as $stepName) {
                TodoStep::create([
                    'step_name' => $stepName,
                    'todo_id' => $todo->id,
                    'completed' => false,
                    'status' => 1,
                ]);
            }
        }

        return response()->json([
            'message' => 'Todo created successfully',
            'todo' => $todo
        ], 201);
    }

    public function storeStep(Request $request, TodoList $todo)
    {
        if ($todo->user_id != Auth::id() || $todo->status == 0) {
            return response()->json(['error' => 'ToDo not found'], Response::HTTP_NOT_FOUND);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|min:1'
        ]);

        TodoStep::create([
            'step_name' => $validatedData['name'],
            'todo_id' => $todo->id,
            'completed' => false,
            'status' => 1,
        ]);

        return response()->json([
            'message' => 'Step created successfully'
        ], 201);
    }

    public function update(Request $request, TodoList $todo)
    {
        if ($todo->user_id != Auth::id() || $todo->status == 0) {
            return response()->json(['error' => 'ToDo not found'], Response::HTTP_NOT_FOUND);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|min:1',
            'description' => 'string',
            'priority' => 'integer',
            'deadline' => 'date',
            'completed' => 'boolean',
            'favorite' => 'boolean'
        ]);

        $todo->task_name = $validatedData['name'];
        $todo->task_description = $validatedData['description'] ?? $todo->task_description;
        $todo->task_priority = $validatedData['priority'] ?? $todo->task_priority;
        $todo->task_deadline = $validatedData['deadline'] ?? $todo->task_deadline;
        $todo->completed = $validatedData['completed'] ?? $todo->completed;
        $todo->favorite = $validatedData['favorite'] ?? $todo->favorite;
        $todo->save();

        return response()->json([
            'message' => 'ToDo updated successfully',
            'todo' => $todo
        ]);
    }

    public function updateStep(Request $request, TodoStep $step)
    {
        if (!$step || $step->todo->user_id != Auth::id() || $step->todo->status == 0) {
            return response()->json(['error' => 'Step not found'], Response::HTTP_NOT_FOUND);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|min:1',
            'completed' => 'required|boolean'
        ]);

        $step->step_name = $validatedData['name'];
        $step->completed = $validatedData['completed'];
        $step->save();

        return response()->json([
            'message' => 'Step updated successfully',
            'step' => $step
        ]);
    }

    public function updateCategory(Request $request, TodoList $todo)
    {
        if ($todo->user_id != Auth::id() || $todo->status == 0) {
            return response()->json(['error' => 'ToDo not found'], Response::HTTP_NOT_FOUND);
        }

        $validatedData = $request->validate([
            'category.*' => 'integer'
        ]);

        TodoCategory::where('todo_id', $todo->id)->update(['status' => 0]);

        foreach ($validatedData['category'] as $categoryId) {
            $categoryExists = Category::where('id', $categoryId)
                ->where('status', 1)
                ->where('user_id', Auth::id())
                ->first();

            if ($categoryExists) {
                $todoCategoryExists = TodoCategory::where('todo_id', $todo->id)
                    ->where('category_id', $categoryId)
                    ->first();

                if (!$todoCategoryExists) {
                    TodoCategory::create([
                        'todo_id' => $todo->id,
                        'category_id' => $categoryId,
                        'status' => 1,
                    ]);
                } else {
                    $todoCategoryExists->status = 1;
                    $todoCategoryExists->save();
                }
            }
        }

        return response()->json(['message' => 'Categories updated successfully']);
    }

    public function show(TodoList $todo)
    {
        if ($todo->user_id != Auth::id() || $todo->status == 0) {
            return response()->json(['error' => 'ToDo not found'], Response::HTTP_NOT_FOUND);
        }

        $todoCategory = TodoCategory::where('todo_id', $todo->id)
            ->where('status', 1)
            ->get();

        $todoStep = TodoStep::where('todo_id', $todo->id)
            ->where('status', 1)
            ->get();

        return response()->json([
            'todo' => $todo,
            'todo_categories' => $todoCategory,
            'todo_step' => $todoStep
        ]);
    }

    public function destroy(TodoList $todo)
    {
        if ($todo->user_id != Auth::id() || $todo->status == 0) {
            return response()->json(['error' => 'ToDo not found'], Response::HTTP_NOT_FOUND);
        }

        $todo->status = 0;
        $todo->save();

        return response()->json(['message' => 'ToDo deleted successfully']);
    }

    public function destroyStep(TodoStep $step)
    {
        if (!$step || $step->todo->user_id != Auth::id() || $step->todo->status == 0 || $step->status == 0) {
            return response()->json(['error' => 'Step not found'], Response::HTTP_NOT_FOUND);
        }

        $step->status = 0;
        $step->save();

        return response()->json(['message' => 'Step deleted successfully']);
    }

    public function favorite($priority = null)
    {
        $list = TodoList::where('user_id', Auth::id())
            ->where('favorite', true)
            ->where('status', 1)
            ->when($priority >= 1 && $priority <= 3, function ($query) use ($priority) {
                return $query->where('task_priority', $priority);
            })->get();

        return response()->json([
            'favorite_list' => $list
        ]);
    }

    public function complete(Request $request, TodoList $todo)
    {
        $validatedData = $request->validate([
            'completed' => 'required|bool'
        ]);


        if ($todo->user_id != Auth::id() || $todo->status == 0) {
            return response()->json(['error' => 'ToDo not found'], Response::HTTP_NOT_FOUND);
        }

        $todo->completed = $validatedData['completed'];
        $todo->save();

        return response()->json([
            'message' => 'ToDo ' . ($todo->completed ? 'completed' : 'not completed') . ' successfully'
        ]);
    }

    public function taskFavorite(Request $request, TodoList $todo)
    {
        $validatedData = $request->validate([
            'favorite' => 'required|bool'
        ]);


        if ($todo->user_id != Auth::id() || $todo->status == 0) {
            return response()->json(['error' => 'ToDo not found'], Response::HTTP_NOT_FOUND);
        }

        $todo->favorite = $validatedData['favorite'];
        $todo->save();

        return response()->json([
            'message' => 'ToDo ' . ($todo->favorite ? 'favorite' : 'not favorite') . ' successfully'
        ]);
    }

    public function stepComplete(TodoStep $step)
    {
        if (!$step || $step->todo->user_id != Auth::id() || $step->todo->status == 0) {
            return response()->json(['error' => 'Step not found'], Response::HTTP_NOT_FOUND);
        }

        $step->completed = true;
        $step->save();

        return response()->json([
            'message' => 'Step completed successfully'
        ]);
    }

    public function taskByDate($priority = null)
    {
        $tasksByDeadline = TodoList::where('user_id', Auth::id())
            ->where('status', 1)
            ->whereNotNull('task_deadline')
            ->orderBy('task_deadline')
            ->when($priority >= 1 && $priority <= 3, function ($query) use ($priority) {
                return $query->where('task_priority', $priority);
            })->get()
            ->groupBy('task_deadline');

        return response()->json([
            'dates' => $tasksByDeadline
        ]);
    }

    public function weeklyTasks($priority = null)
    {
        $now = Carbon::now();

        $thisWeekTasks = TodoList::where('user_id', Auth::id())
            ->where('status', 1)
            ->whereNotNull('task_deadline')
            ->whereBetween('task_deadline', [$now->startOfWeek()->format('Y-m-d'), $now->endOfWeek()->format('Y-m-d')])
            ->orderBy('task_deadline')
            ->when($priority >= 1 && $priority <= 3, function ($query) use ($priority) {
                return $query->where('task_priority', $priority);
            })->get()
            ->groupBy('task_deadline');

        return response()->json([
            'weekly_tasks' => $thisWeekTasks
        ]);
    }

    public function todayTasks($priority = null)
    {
        $today_tasks = TodoList::where('user_id', Auth::id())
            ->where('status', 1)
            ->whereNotNull('task_deadline')
            ->whereDate('task_deadline', now()->format('Y-m-d'))
            ->when($priority >= 1 && $priority <= 3, function ($query) use ($priority) {
                return $query->where('task_priority', $priority);
            })->orderBy('task_priority')
            ->orderBy('created_date')
            ->get();

        return response()->json([
            'today_tasks' => $today_tasks
        ]);
    }

}
