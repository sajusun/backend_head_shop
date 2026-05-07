<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Size;
use App\Models\Amenity;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    use ApiResponse;

    private function getModel($type)
    {
        switch ($type) {
            case 'colors':
                return Color::class;
            case 'sizes':
                return Size::class;
            case 'amenities':
                return Amenity::class;
            default:
                return null;
        }
    }

    public function index($type)
    {
        try {
            $modelClass = $this->getModel($type);
            if (!$modelClass) return $this->error('Invalid attribute type', 400);

            $items = $modelClass::latest()->get();
            return $this->success('Successfully retrieve', $items);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    public function store(Request $request, $type)
    {
        $modelClass = $this->getModel($type);
        if (!$modelClass) return $this->error('Invalid attribute type', 400);

        $rules = [
            'name' => 'required|string|max:255',
            'status' => 'in:active,inactive',
        ];

        if ($type === 'colors') {
            $rules['hex_code'] = 'nullable|string|max:20';
        } elseif ($type === 'sizes') {
            $rules['short'] = 'nullable|string|max:10';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $data = $validator->validated();
            $slug = Str::slug($data['name']);
            
            // Ensure slug uniqueness
            $count = $modelClass::where('slug', 'like', $slug . '%')->count();
            $data['slug'] = $count > 0 ? $slug . '-' . ($count + 1) : $slug;
            
            $item = $modelClass::create($data);
            return $this->success(ucfirst(Str::singular($type)) . ' created successfully', $item, false, null, true, 201);
        } catch (\Throwable $th) {
            Log::error('Attribute Store Error: ' . $th->getMessage());
            return $this->error('Failed to create attribute: ' . $th->getMessage(), 500);
        }
    }

    public function update(Request $request, $type, $id)
    {
        try {
            $modelClass = $this->getModel($type);
            if (!$modelClass) return $this->error('Invalid attribute type', 400);

            $item = $modelClass::findOrFail($id);

            $rules = [
                'name' => 'string|max:255',
                'status' => 'in:active,inactive',
            ];

            if ($type === 'colors') {
                $rules['hex_code'] = 'nullable|string|max:20';
            } elseif ($type === 'sizes') {
                $rules['short'] = 'nullable|string|max:10';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $data = $validator->validated();
            if (isset($data['name'])) {
                $slug = Str::slug($data['name']);
                $count = $modelClass::where('slug', 'like', $slug . '%')->where('id', '!=', $id)->count();
                $data['slug'] = $count > 0 ? $slug . '-' . ($count + 1) : $slug;
            }

            $item->update($data);
            return $this->success(ucfirst(Str::singular($type)) . ' updated successfully', $item);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Attribute not found', 404);
        } catch (\Throwable $th) {
            Log::error('Attribute Update Error: ' . $th->getMessage());
            return $this->error('Failed to update attribute: ' . $th->getMessage(), 500);
        }
    }

    public function destroy($type, $id)
    {
        try {
            $modelClass = $this->getModel($type);
            if (!$modelClass) return $this->error('Invalid attribute type', 400);

            $item = $modelClass::findOrFail($id);
            $item->delete();

            return $this->success(ucfirst(Str::singular($type)) . ' deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Attribute not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
