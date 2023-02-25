<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\Request;

class TicketCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = TicketCategory::all();
        return view('moderator.ticket.category')->with("categories",$categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
        ]);

        TicketCategory::create($request->all());


        return redirect(route("moderator.ticket.category.index"))->with("success",__("Category created"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'category' => 'required|int',
            'name' => 'required|string|max:191',
        ]);

        $category = TicketCategory::where("id",$request->category)->firstOrFail();

        $category->name = $request->name;
        $category->save();

        return redirect()->back()->with("success",__("Category name updated"));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = TicketCategory::where("id",$id)->firstOrFail();

        if($category->id == 5 ){ //cannot delete "other" category
            return back()->with("error","You cannot delete that category");
        }

        $tickets = Ticket::where("ticketcategory_id",$category->id)->get();

        foreach($tickets as $ticket){
            $ticket->ticketcategory_id = "5";
            $ticket->save();
        }

        $category->delete();

        return redirect()
            ->route('moderator.ticket.category.index')
            ->with('success', __('Category removed'));
    }

    public function datatable()
    {
        $query = TicketCategory::withCount("tickets");

        return datatables($query)
            ->addColumn('name', function ( TicketCategory $category) {
                return $category->name;
            })
            ->editColumn('tickets', function ( TicketCategory $category) {
                return $category->tickets_count;
            })
            ->addColumn('actions', function (TicketCategory $category) {
                return '
                           <form class="d-inline" onsubmit="return submitResult();" method="post" action="'.route('moderator.ticket.category.destroy', $category->id).'">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                           <button data-content="'.__('Delete').'" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm btn-danger mr-1"><i class="fas fa-trash"></i></button>
                       </form>
                ';
            })
            ->editColumn('created_at', function (TicketCategory $category) {
                return $category->created_at ? $category->created_at->diffForHumans() : '';
            })
            ->rawColumns(['actions'])
            ->make();
    }
}
