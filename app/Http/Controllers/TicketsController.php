<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Ticket;
use App\Models\TicketBlacklist;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\Ticket\Admin\AdminCreateNotification;
use App\Notifications\Ticket\Admin\AdminReplyNotification;
use App\Notifications\Ticket\User\CreateNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class TicketsController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('user_id', Auth::user()->id)->paginate(10);
        $ticketcategories = TicketCategory::all();

        return view('ticket.index', compact('tickets', 'ticketcategories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
                'title' => 'required',
                'ticketcategory' => 'required',
                'priority' => 'required',
                'message' => 'required',]
        );
        $ticket = new Ticket([
                'title' => $request->input('title'),
                'user_id' => Auth::user()->id,
                'ticket_id' => strtoupper(Str::random(8)),
                'ticketcategory_id' => $request->input('ticketcategory'),
                'priority' => $request->input('priority'),
                'message' => $request->input('message'),
                'status' => 'Open',
                'server' => $request->input('server'),]
        );
        $ticket->save();
        $user = Auth::user();
        if (config('SETTINGS::TICKET:NOTIFY') == "all") {
            $admin = User::where('role', 'admin')->orWhere('role', 'mod')->get();
        }
        if (config('SETTINGS::TICKET:NOTIFY') == "admin") {
            $admin = User::where('role', 'admin')->get();
        }
        if (config('SETTINGS::TICKET:NOTIFY') == "moderator") {
            $admin = User::where('role', 'mod')->get();
        }
        $user->notify(new CreateNotification($ticket));
        if (config('SETTINGS::TICKET:NOTIFY') != "none") {
            Notification::send($admin, new AdminCreateNotification($ticket, $user));
        }

        return redirect()->route('ticket.index')->with('success', __('A ticket has been opened, ID: #') . $ticket->ticket_id);
    }

    public function show($ticket_id)
    {
        try {
            $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
        } catch (Exception $e) {
            return redirect()->back()->with('warning', __('Ticket not found on the server. It potentially got deleted earlier'));
        }
        $ticketcomments = $ticket->ticketcomments;
        $ticketcategory = $ticket->ticketcategory;
        $server = Server::where('id', $ticket->server)->first();

        return view('ticket.show', compact('ticket', 'ticketcategory', 'ticketcomments', 'server'));
    }

    public function reply(Request $request)
    {
        //check in blacklist
        $check = TicketBlacklist::where('user_id', Auth::user()->id)->first();
        if ($check && $check->status == 'True') {
            return redirect()->route('ticket.index')->with('error', __("You can't reply a ticket because you're on the blacklist for a reason: '" . $check->reason . "', please contact the administrator"));
        }
        $this->validate($request, ['ticketcomment' => 'required']);
        try {
            $ticket = Ticket::where('id', $request->input('ticket_id'))->firstOrFail();
        } catch (Exception $e) {
            return redirect()->back()->with('warning', __('Ticket not found on the server. It potentially got deleted earlier'));
        }
        $ticket->status = 'Client Reply';
        $ticket->update();
        $ticketcomment = TicketComment::create([
            'ticket_id' => $request->input('ticket_id'),
            'user_id' => Auth::user()->id,
            'ticketcomment' => $request->input('ticketcomment'),
            'message' => $request->input('message'),
        ]);
        $user = Auth::user();
        $admin = User::where('role', 'admin')->orWhere('role', 'mod')->get();
        $newmessage = $request->input('ticketcomment');
        Notification::send($admin, new AdminReplyNotification($ticket, $user, $newmessage));

        return redirect()->back()->with('success', __('Your comment has been submitted'));
    }

    public function create()
    {
        //check in blacklist
        $check = TicketBlacklist::where('user_id', Auth::user()->id)->first();
        if ($check && $check->status == 'True') {
            return redirect()->route('ticket.index')->with('error', __("You can't make a ticket because you're on the blacklist for a reason: '" . $check->reason . "', please contact the administrator"));
        }
        $ticketcategories = TicketCategory::all();
        $servers = Auth::user()->servers;

        return view('ticket.create', compact('ticketcategories', 'servers'));
    }

    public function changeStatus($ticket_id)
    {
        try {
            $ticket = Ticket::where('user_id', Auth::user()->id)->where("ticket_id", $ticket_id)->firstOrFail();
        } catch (Exception $e) {
            return redirect()->back()->with('warning', __('Ticket not found on the server. It potentially got deleted earlier'));
        }
        if ($ticket->status == "Closed") {
            $ticket->status = "Reopened";
            $ticket->save();
            return redirect()->back()->with('success', __('A ticket has been reopened, ID: #') . $ticket->ticket_id);
        }
        $ticket->status = "Closed";
        $ticket->save();
        return redirect()->back()->with('success', __('A ticket has been closed, ID: #') . $ticket->ticket_id);
    }

    public function dataTable()
    {
        $query = Ticket::where('user_id', Auth::user()->id)->get();

        return datatables($query)
            ->addColumn('category', function (Ticket $tickets) {
                return $tickets->ticketcategory->name;
            })
            ->editColumn('title', function (Ticket $tickets) {
                return '<a class="text-info"  href="' . route('ticket.show', ['ticket_id' => $tickets->ticket_id]) . '">' . '#' . $tickets->ticket_id . ' - ' . htmlspecialchars($tickets->title) . '</a>';
            })
            ->editColumn('status', function (Ticket $tickets) {
                switch ($tickets->status) {
                    case 'Reopened':
                    case 'Open':
                        $badgeColor = 'badge-success';
                        break;
                    case 'Closed':
                        $badgeColor = 'badge-danger';
                        break;
                    case 'Answered':
                        $badgeColor = 'badge-info';
                        break;
                    default:
                        $badgeColor = 'badge-warning';
                        break;
                }

                return '<span class="badge ' . $badgeColor . '">' . $tickets->status . '</span>';
            })
            ->editColumn('priority', function (Ticket $tickets) {
                return __($tickets->priority);
            })
            ->editColumn('updated_at', function (Ticket $tickets) {
                return ['display' => $tickets->updated_at ? $tickets->updated_at->diffForHumans() : '',
                    'raw' => $tickets->updated_at ? strtotime($tickets->updated_at) : ''];
            })
            ->addColumn('actions', function (Ticket $tickets) {
                $statusButtonColor = ($tickets->status == "Closed") ? 'btn-success' : 'btn-warning';
                $statusButtonIcon = ($tickets->status == "Closed") ? 'fa-redo' : 'fa-times';
                $statusButtonText = ($tickets->status == "Closed") ? __('Reopen') : __('Close');

                return '
                            <a data-content="' . __('View') . '" data-toggle="popover" data-trigger="hover" data-placement="top" href="' . route('ticket.show', ['ticket_id' => $tickets->ticket_id]) . '" class="btn btn-sm text-white btn-info mr-1"><i class="fas fa-eye"></i></a>
                            <form class="d-inline"  method="post" action="' . route('ticket.changeStatus', ['ticket_id' => $tickets->ticket_id]) . '">
                                ' . csrf_field() . '
                                ' . method_field('POST') . '
                            <button data-content="' . __($statusButtonText) . '" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm text-white ' . $statusButtonColor . '  mr-1"><i class="fas ' . $statusButtonIcon . '"></i></button>
                            </form>

                            </form>
                ';
            })
            ->rawColumns(['category', 'title', 'status', 'updated_at', "actions"])
            ->make(true);
    }
}
