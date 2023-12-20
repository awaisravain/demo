<?php

namespace App\Http\Controllers;

use App\Models\ToDoList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ToDoController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $searchTerm = $request->input('search');
        $query = ToDoList::where('user_id', $userId);
        if ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }
        $perPage = $request->input('per_page', 10);
        $todolists = $query->select('title', 'description')->paginate($perPage);

        return response()->json(['data' => $todolists]);
    }

    public function show(ToDoList $todolist)
    {
        $todolist->setVisible(['title', 'description']);
        return response()->json(['data' => $todolist]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $todolist = ToDoList::create(['title' => $request->title, 'description' => $request->description, 'user_id' => auth()->id()]);

        return response()->json(['message' => 'Data Added Successfuly']);
    }

    public function update(Request $request, ToDoList $todolist)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $todolist->update($request->all());

        return response()->json(['message' => 'Data updated Successfuly']);
    }

    public function destroy(ToDoList $todolist)
    {
        $todolist->delete();

        return response()->json(['message' => 'Data deleted successfully']);
    }
}
