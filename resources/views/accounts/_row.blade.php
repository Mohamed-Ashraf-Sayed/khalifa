@php($children = $byParent->get($account->id))
<tr @class(['opacity-50' => ! $account->is_active])>
    <td class="font-monospace">{{ $account->code }}</td>
    <td>
        <span style="padding-inline-start: {{ $depth * 24 }}px">
            @if ($account->is_group)
                <i class="fa-solid fa-folder text-warning ms-1"></i>
            @else
                <i class="fa-regular fa-file ms-1 text-muted"></i>
            @endif
            <span @class(['fw-bold' => $account->is_group])>{{ $account->name }}</span>
        </span>
    </td>
    <td><span class="badge text-bg-{{ $account->normal_balance === 'debit' ? 'primary' : 'success' }}">{{ \App\Models\Account::NORMAL[$account->normal_balance] ?? $account->normal_balance }}</span></td>
    <td class="text-end {{ $account->is_group ? 'text-muted' : 'fw-semibold' }}">
        @if ($account->is_group) — @else {{ number_format((float) $account->balance(), 2) }} @endif
    </td>
    <td class="text-end">
        @can('accounting.view')
            @unless ($account->is_group)
                <a href="{{ route('accounting.ledger', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-secondary" title="كشف الحساب"><i class="fa-solid fa-book-open"></i></a>
            @endunless
        @endcan
        @can('accounting.edit')
            <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary" title="تعديل"><i class="fa-solid fa-pen"></i></a>
        @endcan
        @can('accounting.delete')
            <form method="POST" action="{{ route('accounts.destroy', $account) }}" class="d-inline"
                  data-confirm="متأكد من حذف الحساب؟">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
            </form>
        @endcan
    </td>
</tr>
@if ($children)
    @foreach ($children as $child)
        @include('accounts._row', ['account' => $child, 'depth' => $depth + 1, 'byParent' => $byParent])
    @endforeach
@endif
