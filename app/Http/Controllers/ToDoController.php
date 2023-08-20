<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\TodoList;
use App\Models\TodoCategory;
use App\Models\TodoStep;

class ToDoController extends Controller
{
    public function index()
    {
        $list = TodoList::where('user_id', Auth::id())
            ->where('status', 1)
            ->get();

        return response()->json([
            'todo_list' => $list
        ]);
    }

    public function showCategory(int $category_id)
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
            ->get();

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
            'completed' => 'required|boolean'
        ]);

        $todo->task_name = $validatedData['name'];
        $todo->task_description = $validatedData['description'] ?? null;
        $todo->task_priority = $validatedData['priority'] ?? 2;
        $todo->task_deadline = $validatedData['deadline'] ?? null;
        $todo->completed = $validatedData['completed'] ?? false;
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
}
