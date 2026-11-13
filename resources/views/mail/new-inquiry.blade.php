Новая заявка с сайта MikroTik Ashgabat

Номер: #{{ $inquiry->id }}
Тип: {{ $inquiry->type->getLabel() }}
Имя: {{ $inquiry->name }}
Телефон: {{ $inquiry->phone }}
Эл. почта: {{ $inquiry->email ?? '—' }}
Связь: {{ $inquiry->preferred_contact->getLabel() }}
@if ($inquiry->product_snapshot)
Товар: {{ $inquiry->product_snapshot }}
@endif
@if ($inquiry->service)
Услуга: {{ $inquiry->service->translate('title', 'ru') }}
@endif

Сообщение:
{{ $inquiry->message ?: '—' }}

Страница: {{ $inquiry->page_url }}
Открыть в панели: {{ url('/admin/inquiries/'.$inquiry->id) }}
