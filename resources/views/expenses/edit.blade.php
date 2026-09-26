@extends('layouts.app')

@section('title', 'Edit Expense')
@section('page-title', 'Edit Expense')
@section('page-subtitle', $expense->expense_number)

@section('content')
    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            <ul class="list-disc ml-5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @include('expenses._form')
        </form>
    </div>
@endsection
