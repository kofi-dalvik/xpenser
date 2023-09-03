<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $from = now()->startOf('month')->format('Y-m-d') . ' 00:00:00';
        $to = now()->endOf('month')->format('Y-m-d') . ' 23:59:59';

        $params = $this->getDashboardData($from, $to);

        return Inertia::render('Dashboard/Index', $params);
    }

    public function updateBudgets(Request $request)
    {
        //validate request limit must be 0 or more
        $request->validate([
            'limit' => 'required|numeric|min:0',
        ]);

        $user_id = auth()->user()->id;

        $budget = Budget::where('user_id', $user_id)
                ->where('month', now()->format('m-Y'))
                ->first();

        if (!$budget) {
            $budget = new Budget();
            $budget->user_id = $user_id;
            $budget->month = now()->format('m-Y');
        }

        $budget->limit = $request->input('limit');
        $budget->save();

        return redirect()->back()->with([
            'message' => 'Budget updated successfully'
        ]);
    }

    public function storeCategory(Request $request)
    {
        //validate request
        $request->validate([
            'id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255|unique:categories,name',
            'icon' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->id) {
            $category = Category::main()->find($request->id);
        } else {
            $category = new Category();
        }

        $category->name = $request->input('name');
        $category->parent_id = $request->input('parent_id');
        $category->ui = [
            'icon' => $request->input('icon'),
            'color' => $request->input('color'),
        ];
        $category->save();

        return redirect()->back()->with([
            'message' => 'Category created successfully'
        ]);
    }

    public function getAnalytics(Request $request)
    {
        $from = $request->input('from') . ' 00:00:00';
        $to = $request->input('to') . ' 23:59:59';

        $data = $this->getDashboardData($from, $to);

        return response()->json($data);
    }

    public function getDashboardData(string $from, string $to)
    {
        return [
            'cats' => $this->getCategories($from, $to),
            'trans' => $this->getTransactions($from, $to),
            'smry' => $this->getSummary($from, $to),
            'bdgt' => $this->getBudget(),
        ];
    }

    public function getCategories(string $from, string $to)
    {
        $user_id = auth()->user()->id;

        $categories = Category::main($user_id)->get();

        $categories = $categories->map(function ($category) use ($from, $to, $user_id) {
            $category->total = Transaction::where('user_id', $user_id)
                        ->where('category_id', $category->id)
                        ->whereBetween('date', [$from, $to])
                        ->sum('amount');

            return $category;
        });

        //sort by descending order of total
        $categories = $categories->sortByDesc('total')->sortByDesc('created_at')->values();

        return $categories;
    }

    public function getTransactions(string $from, string $to)
    {
        $user = auth()->user()->id;

        return Transaction::with('category')
            ->where('user_id', $user)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date', 'desc')
            ->paginate(100);
    }

    public function getSummary(string $from, string $to)
    {
        $transactions = Transaction::select('type')
            ->selectRaw('SUM(amount) as total_amount')
            ->whereBetween('date', [$from, $to])
            ->groupBy('type')
            ->get();

        $data = [];

        foreach (Transaction::TYPES as $type) {
            $data[$type] = 0;
        }

        foreach ($transactions as $transaction) {
            $data[$transaction->type] = $transaction->total_amount;
        }

        $data['balance'] = $data[Transaction::INCOME] - $data[Transaction::EXPENSE];

        return $data;
    }

    public function getBudget()
    {
        $from = now()->startOf('month')->format('Y-m-d') . ' 00:00:00';
        $to = now()->endOf('month')->format('Y-m-d') . ' 23:59:59';

        $user_id = auth()->user()->id;

        $budget = Budget::where('user_id', $user_id)
                    ->where('month', now()->format('m-Y'))
                    ->first();

        $limit = $budget ? $budget->limit : 1000;

        $spend = Transaction::where('user_id', $user_id)
            ->whereBetween('date', [$from, $to])
            ->where('type', Transaction::EXPENSE)
            ->sum('amount');

        return [
            'limit' => $limit,
            'spend' => $spend,
            'balance' => $limit - $spend,
        ];
    }
}
