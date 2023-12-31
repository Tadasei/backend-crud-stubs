<?php

use App\Http\Controllers\StubController;
use Illuminate\Support\Facades\Route;

Route::delete("/stubs", [StubController::class, "destroy"])->name(
	"stubs.destroy"
);

Route::resource("stubs", StubController::class)->except(["edit", "destroy"]);

Route::put("/lazy/stubs", [StubController::class, "lazy"])->name("stubs.lazy");
