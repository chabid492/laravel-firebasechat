<?php

namespace App\DataTables;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;
use Yajra\DataTables\DataTablesEditor;

class StudentsDataTableEditor extends DataTablesEditor
{
    protected $model = Student::class;
    protected $actions = ['create', 'edit', 'remove', 'upload'];

    protected $uploadDir = 'uploads/student';

    protected $imageWidth = 500;

    /**
     * Get create action validation rules.
     *
     * @return array
     */
    public function createRules()
    {
        return [
            'email' => 'required|email|unique:' . $this->resolveModel()->getTable(),
            'name'  => 'required',
            'username'  => 'required',
        ];
    }

    /**
     * Get edit action validation rules.
     *
     * @param Model $model
     * @return array
     */
    public function editRules(Model $model)
    {
        return [
            'email' => 'sometimes|required|email|' . Rule::unique($model->getTable())->ignore($model->getKey()),
            'name'  => 'sometimes|required',
        ];
    }

    /**
     * Get remove action validation rules.
     *
     * @param Model $model
     * @return array
     */

    public function upload(Request $request)
    {
        try {
            $request->validate($this->uploadRules());

            $type = $request->input('image');
            $dir  = $this->uploadDir . $type;

            $uploadedFile = $request->file('upload');
            $filename     = time() . '.png';

            $uploadedFile->move(public_path($dir), $filename);

            $method = 'optimize' . Str::title($type);
            if (method_exists($this, $method)) {
                $id = $this->{$method}($dir, $filename);
            } else {
                $id = $this->optimize($dir, $filename);
            }

            return response()->json([
                'data'   => [],
                'upload' => [
                    'id' => $id,
                ],
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'data'        => [],
                'fieldErrors' => [
                    [
                        'name'   => $request->get('image'),
                        'status' => $exception->getMessage(),
                    ],
                ],
            ]);
        }
    }
    protected function optimize($dir, $filename)
    {
        $path = public_path($dir . '/' . $filename);

        Image::load($path)
            ->width($this->imageWidth)
            ->format(Manipulations::FORMAT_PNG)
            ->optimize()
            ->save();

        return $dir . '/' . $filename;
    }

    public function removeRules(Model $model)
    {
        return [];
    }
}
