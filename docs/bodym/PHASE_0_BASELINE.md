# BodyM Phase 0: Contract, Baseline, and Rollback

## Purpose

Phase 0 freezes the future BodyM output contract without changing the current
production measurement behavior. The BodyM path remains disabled until a
trained, evaluated model is available.

## Frozen BodyM v1 contract

- Contract version: `bodym.v1`
- Measurement method: `bodym_ml`
- Unit: centimeters (`cm`)
- Canonical schema: `docs/bodym/measurement-contract-v1.json`
- Laravel metadata: `config/bodym.php`
- Python metadata: `python-cv/bodym_contract.py`

The 14 allowed output fields are:

1. `ankle_girth`
2. `arm_length`
3. `bicep_girth`
4. `calf_girth`
5. `chest_girth`
6. `forearm_girth`
7. `height`
8. `hip_girth`
9. `leg_length`
10. `shoulder_breadth`
11. `shoulder_to_crotch`
12. `thigh_girth`
13. `waist_girth`
14. `wrist_girth`

Fields such as neck, knee, inseam, outseam, rise, and shirt length are not
part of BodyM v1 and must not be presented as BodyM model outputs.

## Legacy baseline

- Baseline commit: `5f52f6b` (`Clarify reference errors with contrast editor`)
- Legacy method: `multiview_cv`
- Legacy output count: 19 fields
- Legacy inputs: front, side, and back photos plus A4/KTP reference data
- Legacy implementation: `python-cv/measurement.py`
- Laravel integration: `app/Services/CVMeasurementService.php`
- UI/controller: `app/Http/Controllers/User/MeasurementController.php`

The legacy method estimates circumferences using silhouette widths, side depth,
ellipse geometry, pose landmarks, and fixed plausibility ranges. Its confidence
is a quality heuristic; it is not a learned BodyM uncertainty estimate.

Fresh verification evidence for this baseline must be recorded in the Phase 0
task before the phase is closed. Test counts are intentionally not hard-coded in
this file because they change as contract tests are added.

## Activation and rollback

BodyM is controlled by environment configuration:

```dotenv
BODYM_ENABLED=false
BODYM_MODEL_VERSION=untrained
```

Phase 0 does not route any request through BodyM. Future integration must read
`config('bodym.enabled')` and preserve the legacy path while the flag is false.

Rollback procedure after BodyM integration:

1. Set `BODYM_ENABLED=false` on the target environment.
2. Run `php artisan config:clear` followed by `php artisan config:cache`.
3. Restart the Python CV service if it also receives the BodyM flag.
4. Check `/health` and run one legacy measurement smoke test.
5. Confirm that `measurement_method` is `multiview_cv`.

If configuration rollback is insufficient, deploy the last verified legacy
commit recorded in the release notes. Do not delete BodyM migrations or user
measurements during rollback.

## Phase 0 acceptance criteria

- Laravel, Python, and JSON Schema contain the same 14 ordered fields.
- BodyM is disabled by default.
- Existing measurement behavior is unchanged.
- Contract drift is covered by automated tests.
- Laravel tests, Python tests, frontend build, and diff checks pass freshly.
